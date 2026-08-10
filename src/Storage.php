<?php

declare(strict_types=1);

/**
 * A JSON file holding a list of records.
 *
 * The point of this class is `mutate()`. Every write in the old codebase was an
 * unlocked read-modify-write: two requests arriving together would both read the
 * same array, both append, and the second `file_put_contents` would silently
 * discard the first one's record. Here the read and the write happen inside a
 * single `flock(LOCK_EX)`, so concurrent uploads queue instead of clobbering.
 */
final class Storage
{
    public function __construct(private readonly string $path)
    {
    }

    /**
     * Read the records. Returns [] for a missing, empty, or corrupt file — a
     * broken data file should render an empty gallery, not a fatal error.
     *
     * @return array<int, array<string, mixed>>
     */
    public function read(): array
    {
        if (!is_file($this->path)) {
            return [];
        }

        $handle = @fopen($this->path, 'r');
        if ($handle === false) {
            return [];
        }

        try {
            // Shared lock: concurrent readers are fine, but we must not read
            // while a writer is midway through truncating and rewriting.
            flock($handle, LOCK_SH);
            $raw = stream_get_contents($handle);
        } finally {
            flock($handle, LOCK_UN);
            fclose($handle);
        }

        return $this->decode($raw === false ? '' : $raw);
    }

    /**
     * Read-modify-write under an exclusive lock.
     *
     * The mutator receives the current records by reference; whatever it leaves
     * in that array is written back. Its return value is passed through to the
     * caller, so a mutator can report what it did:
     *
     *     $added = $storage->mutate(function (array &$files) use ($record) {
     *         $files[] = $record;
     *         return $record;
     *     });
     *
     * @template T
     * @param callable(array<int, array<string, mixed>>): T $mutator
     * @return T
     */
    public function mutate(callable $mutator): mixed
    {
        $this->ensureDirectory();

        // 'c+' creates the file if absent and, unlike 'w+', does not truncate
        // before we have the lock — truncating first would expose an empty file
        // to concurrent readers.
        $handle = fopen($this->path, 'c+');
        if ($handle === false) {
            throw new RuntimeException("Cannot open data file for writing: {$this->path}");
        }

        try {
            if (!flock($handle, LOCK_EX)) {
                throw new RuntimeException("Cannot lock data file: {$this->path}");
            }

            $raw = stream_get_contents($handle);
            $records = $this->decode($raw === false ? '' : $raw);

            $result = $mutator($records);

            $encoded = json_encode(
                array_values($records),
                JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
            );
            if ($encoded === false) {
                throw new RuntimeException('Failed to encode records: ' . json_last_error_msg());
            }

            rewind($handle);
            if (ftruncate($handle, 0) === false || fwrite($handle, $encoded) === false) {
                throw new RuntimeException("Failed to write data file: {$this->path}");
            }
            fflush($handle);

            return $result;
        } finally {
            flock($handle, LOCK_UN);
            fclose($handle);
        }
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function decode(string $raw): array
    {
        if (trim($raw) === '') {
            return [];
        }

        $decoded = json_decode($raw, true);

        return is_array($decoded) ? array_values(array_filter($decoded, 'is_array')) : [];
    }

    private function ensureDirectory(): void
    {
        $dir = dirname($this->path);
        if (!is_dir($dir) && !@mkdir($dir, 0775, true) && !is_dir($dir)) {
            throw new RuntimeException("Cannot create data directory: {$dir}");
        }
    }
}
