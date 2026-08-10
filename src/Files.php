<?php

declare(strict_types=1);

/**
 * The file index in data/files.json, plus the bytes in storage/uploads.
 *
 * Records look like:
 *
 *   {
 *     "file_id":       "f_9a1c...",      // random, not sequential
 *     "owner":         "elliot",
 *     "stored_name":   "9a1c....jpg",    // the name on disk
 *     "original_name": "holiday snap.jpg",
 *     "title":         "Holiday snap",
 *     "extension":     "jpg",
 *     "mime":          "image/jpeg",
 *     "kind":          "image",
 *     "size":          482913,
 *     "width":         1920,             // images only
 *     "height":        1080,
 *     "position":      3,
 *     "uploaded_at":   1754812800
 *   }
 *
 * The old schema used `Bild_ID_<n>` — a sequential id derived from the array
 * count, which collided as soon as anything was deleted, and leaked how many
 * files existed. Ids are now random and opaque.
 */
final class Files
{
    public function __construct(
        private readonly Storage $storage,
        private readonly string $uploadPath,
    ) {
    }

    /** @return array<int, array<string, mixed>> */
    public function all(): array
    {
        $files = $this->storage->read();
        usort($files, static fn (array $a, array $b) => ($a['position'] ?? 0) <=> ($b['position'] ?? 0));

        return $files;
    }

    /** @return array<int, array<string, mixed>> */
    public function forOwner(string $owner): array
    {
        return array_values(array_filter(
            $this->all(),
            static fn (array $f) => ($f['owner'] ?? null) === $owner
        ));
    }

    /** @return array<string, mixed>|null */
    public function find(string $fileId): ?array
    {
        foreach ($this->storage->read() as $file) {
            if (($file['file_id'] ?? null) === $fileId) {
                return $file;
            }
        }

        return null;
    }

    /**
     * All files grouped by kind, in FileTypes::KINDS order, skipping empty
     * kinds so the browse page does not render headings for nothing.
     *
     * @return array<string, array<int, array<string, mixed>>>
     */
    public function groupedByKind(): array
    {
        $grouped = [];

        foreach ($this->all() as $file) {
            $grouped[$file['kind'] ?? FileTypes::KIND_OTHER][] = $file;
        }

        $ordered = [];
        foreach (FileTypes::KINDS as $kind) {
            if (!empty($grouped[$kind])) {
                $ordered[$kind] = $grouped[$kind];
            }
        }

        return $ordered;
    }

    /** @return array<string, int> */
    public function countsByKind(): array
    {
        $counts = [];

        foreach ($this->all() as $file) {
            $kind = $file['kind'] ?? FileTypes::KIND_OTHER;
            $counts[$kind] = ($counts[$kind] ?? 0) + 1;
        }

        return $counts;
    }

    public function totalSize(): int
    {
        return array_sum(array_map(static fn (array $f) => (int) ($f['size'] ?? 0), $this->all()));
    }

    /**
     * Append a record. The position is assigned inside the lock, from the
     * current maximum, so concurrent uploads cannot be handed the same slot.
     *
     * @param array<string, mixed> $record
     * @return array<string, mixed> the stored record, with its position filled in
     */
    public function add(array $record): array
    {
        return $this->storage->mutate(static function (array &$files) use ($record): array {
            $maxPosition = 0;
            foreach ($files as $file) {
                $maxPosition = max($maxPosition, (int) ($file['position'] ?? 0));
            }

            $record['position'] = $maxPosition + 1;
            $files[] = $record;

            return $record;
        });
    }

    /**
     * Retitle a file. Only the owner may do so; returns false otherwise, which
     * the caller turns into a 403.
     */
    public function rename(string $fileId, string $owner, string $title): bool
    {
        return $this->storage->mutate(static function (array &$files) use ($fileId, $owner, $title): bool {
            foreach ($files as &$file) {
                if (($file['file_id'] ?? null) === $fileId) {
                    // Ownership is re-checked here rather than trusting a check
                    // the caller may have done against a stale read.
                    if (($file['owner'] ?? null) !== $owner) {
                        return false;
                    }
                    $file['title'] = $title;

                    return true;
                }
            }
            unset($file);

            return false;
        });
    }

    /**
     * Delete a record and its bytes. The record goes first: an orphaned file on
     * disk is harmless, whereas an index entry pointing at a deleted file would
     * render as a broken tile.
     */
    public function delete(string $fileId, string $owner): bool
    {
        $removed = $this->storage->mutate(static function (array &$files) use ($fileId, $owner): ?array {
            foreach ($files as $index => $file) {
                if (($file['file_id'] ?? null) === $fileId) {
                    if (($file['owner'] ?? null) !== $owner) {
                        return null;
                    }
                    unset($files[$index]);
                    $files = array_values($files);

                    return $file;
                }
            }

            return null;
        });

        if ($removed === null) {
            return false;
        }

        $path = $this->pathFor($removed['stored_name'] ?? '');
        if ($path !== null && is_file($path)) {
            @unlink($path);
        }

        return true;
    }

    /**
     * Apply a new ordering.
     *
     * Takes ids only. The old endpoint accepted whole records from the client
     * and wrote them back, so a POST could rewrite any field — including
     * `source`, repointing a record at someone else's file. Here the client can
     * express nothing but an order, and ids it does not own are ignored.
     *
     * @param string[] $orderedIds
     */
    public function reorder(array $orderedIds, string $owner): void
    {
        $this->storage->mutate(static function (array &$files) use ($orderedIds, $owner): void {
            $rank = [];
            foreach (array_values($orderedIds) as $index => $id) {
                if (is_string($id)) {
                    $rank[$id] = $index;
                }
            }

            // Files the request did not mention keep their relative order after
            // the ones it did.
            $offset = count($rank);
            foreach ($files as &$file) {
                if (($file['owner'] ?? null) !== $owner) {
                    continue;
                }
                $id = $file['file_id'] ?? '';
                if (isset($rank[$id])) {
                    $file['position'] = $rank[$id] + 1;
                } else {
                    $file['position'] = ++$offset;
                }
            }
            unset($file);
        });
    }

    /**
     * Absolute path for a stored name, or null if the name is not one we could
     * have written.
     *
     * The index is a file on disk; if it were ever tampered with, a `stored_name`
     * of "../../etc/passwd" would otherwise become a readable download. The
     * pattern below admits only the names generated at upload time.
     */
    public function pathFor(string $storedName): ?string
    {
        if (!preg_match('/^[a-f0-9]{32,64}\.[a-z0-9]{1,5}$/', $storedName)) {
            return null;
        }

        return $this->uploadPath . '/' . $storedName;
    }
}
