<?php

declare(strict_types=1);

/** Raised when an upload is rejected; the message is safe to show the user. */
final class UploadException extends RuntimeException
{
}

/**
 * Validates an incoming $_FILES entry and moves it into storage.
 *
 * The old path checked only the file extension against a list, then moved the
 * file into a web-served directory under a clock-derived name. Three problems:
 * the extension says nothing about the contents, `uniqid()` names are
 * guessable, and anything the web server decided to execute in `uploads/` would
 * have run. Files now land outside the document root under a random name, and
 * the bytes are sniffed and required to agree with the extension.
 */
final class Uploader
{
    public function __construct(
        private readonly Files $files,
        private readonly string $uploadPath,
        private readonly int $maxBytes,
    ) {
    }

    public function maxBytes(): int
    {
        return $this->maxBytes;
    }

    /**
     * @param array<string, mixed> $upload one entry from $_FILES
     * @return array<string, mixed> the stored record
     * @throws UploadException
     */
    public function store(array $upload, string $owner, string $title): array
    {
        $this->assertUploadOk($upload);

        $tmpPath = (string) ($upload['tmp_name'] ?? '');

        // Guards against a caller passing an arbitrary path as tmp_name.
        if (!is_uploaded_file($tmpPath)) {
            throw new UploadException('That upload could not be verified. Please try again.');
        }

        $size = (int) ($upload['size'] ?? 0);
        if ($size <= 0) {
            throw new UploadException('That file is empty.');
        }
        if ($size > $this->maxBytes) {
            throw new UploadException(sprintf(
                'That file is %s, which is over the %s limit.',
                FileTypes::formatSize($size),
                FileTypes::formatSize($this->maxBytes)
            ));
        }

        $originalName = sanitize_filename((string) ($upload['name'] ?? 'file'));
        $extension    = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

        if ($extension === '' || !FileTypes::isAllowedExtension($extension)) {
            throw new UploadException(
                $extension === ''
                    ? 'That file has no extension, so we cannot tell what it is.'
                    : sprintf('“.%s” files are not accepted.', $extension)
            );
        }

        // Sniff the actual contents. An extension is attacker-supplied; this
        // reads the file's own magic bytes.
        $detected = $this->detectMime($tmpPath);
        if (!FileTypes::matchesExtension($extension, $detected)) {
            throw new UploadException(sprintf(
                'That file does not look like a .%s file (detected %s).',
                $extension,
                $detected
            ));
        }

        $kind       = FileTypes::kindForExtension($extension);
        $storedName = random_id(20) . '.' . $extension;
        $target     = $this->uploadPath . '/' . $storedName;

        $this->ensureUploadDirectory();

        if (!move_uploaded_file($tmpPath, $target)) {
            throw new UploadException('We could not save that file. Please try again.');
        }

        // Not executable, and not group/world writable.
        @chmod($target, 0640);

        [$width, $height] = $this->measure($target, $kind);

        return $this->files->add([
            'file_id'       => 'f_' . random_id(12),
            'owner'         => $owner,
            'stored_name'   => $storedName,
            'original_name' => $originalName,
            'title'         => $title !== '' ? $title : $originalName,
            'extension'     => $extension,
            'mime'          => $detected,
            'kind'          => $kind,
            'size'          => $size,
            'width'         => $width,
            'height'        => $height,
            'uploaded_at'   => time(),
        ]);
    }

    /**
     * Translate PHP's upload error codes into something a user can act on.
     *
     * The old code ignored these entirely and only checked `move_uploaded_file`,
     * so a file over `upload_max_filesize` produced a generic "Upload failed."
     *
     * @param array<string, mixed> $upload
     */
    private function assertUploadOk(array $upload): void
    {
        $error = (int) ($upload['error'] ?? UPLOAD_ERR_NO_FILE);

        if ($error === UPLOAD_ERR_OK) {
            return;
        }

        throw match ($error) {
            UPLOAD_ERR_INI_SIZE,
            UPLOAD_ERR_FORM_SIZE  => new UploadException(sprintf(
                'That file is larger than the %s limit.',
                FileTypes::formatSize($this->maxBytes)
            )),
            UPLOAD_ERR_PARTIAL    => new UploadException('That upload was interrupted. Please try again.'),
            UPLOAD_ERR_NO_FILE    => new UploadException('Please choose a file to upload.'),
            UPLOAD_ERR_NO_TMP_DIR => new UploadException('The server has no temporary folder configured.'),
            UPLOAD_ERR_CANT_WRITE => new UploadException('The server could not write the file to disk.'),
            UPLOAD_ERR_EXTENSION  => new UploadException('A server extension blocked that upload.'),
            default               => new UploadException('That upload failed for an unknown reason.'),
        };
    }

    private function detectMime(string $path): string
    {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        if ($finfo === false) {
            throw new UploadException('The server cannot inspect file types right now.');
        }

        try {
            $mime = finfo_file($finfo, $path);
        } finally {
            finfo_close($finfo);
        }

        return is_string($mime) && $mime !== '' ? $mime : 'application/octet-stream';
    }

    /**
     * Dimensions for images, so the grid can reserve space and avoid layout
     * shift. Measured once here rather than on every page render.
     *
     * @return array{0: int|null, 1: int|null}
     */
    private function measure(string $path, string $kind): array
    {
        if ($kind !== FileTypes::KIND_IMAGE) {
            return [null, null];
        }

        $dimensions = @getimagesize($path);

        return $dimensions ? [(int) $dimensions[0], (int) $dimensions[1]] : [null, null];
    }

    private function ensureUploadDirectory(): void
    {
        if (!is_dir($this->uploadPath) && !@mkdir($this->uploadPath, 0775, true) && !is_dir($this->uploadPath)) {
            throw new UploadException('The upload folder is missing and could not be created.');
        }

        if (!is_writable($this->uploadPath)) {
            throw new UploadException('The upload folder is not writable.');
        }
    }
}
