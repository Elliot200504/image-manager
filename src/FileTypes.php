<?php

declare(strict_types=1);

/**
 * The allowlist of what may be uploaded, and how each kind is presented.
 *
 * Categories here are derived from the file itself rather than chosen by the
 * uploader. The old app asked the user to pick from a fixed list (Animals,
 * People, Architecture...) which only made sense for photos; for a general file
 * service the useful grouping is what the thing actually is.
 */
final class FileTypes
{
    public const KIND_IMAGE    = 'image';
    public const KIND_DOCUMENT = 'document';
    public const KIND_AUDIO    = 'audio';
    public const KIND_VIDEO    = 'video';
    public const KIND_ARCHIVE  = 'archive';
    public const KIND_OTHER    = 'other';

    /**
     * Extension => [allowed MIME types, kind].
     *
     * Note the absence of SVG. An SVG is a script-capable document; serving one
     * inline from our own origin would be stored XSS. Adding it back means
     * forcing `Content-Disposition: attachment` for it and never previewing it.
     *
     * @var array<string, array{0: string[], 1: string}>
     */
    private const TYPES = [
        // Images
        'jpg'  => [['image/jpeg'], self::KIND_IMAGE],
        'jpeg' => [['image/jpeg'], self::KIND_IMAGE],
        'png'  => [['image/png'], self::KIND_IMAGE],
        'gif'  => [['image/gif'], self::KIND_IMAGE],
        'webp' => [['image/webp'], self::KIND_IMAGE],
        'bmp'  => [['image/bmp', 'image/x-ms-bmp'], self::KIND_IMAGE],

        // Documents
        'pdf'  => [['application/pdf'], self::KIND_DOCUMENT],
        'txt'  => [['text/plain'], self::KIND_DOCUMENT],
        'md'   => [['text/plain', 'text/markdown'], self::KIND_DOCUMENT],
        'csv'  => [['text/plain', 'text/csv', 'application/csv'], self::KIND_DOCUMENT],
        'rtf'  => [['application/rtf', 'text/rtf'], self::KIND_DOCUMENT],
        'doc'  => [['application/msword'], self::KIND_DOCUMENT],
        'docx' => [['application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/zip'], self::KIND_DOCUMENT],
        'xls'  => [['application/vnd.ms-excel'], self::KIND_DOCUMENT],
        'xlsx' => [['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/zip'], self::KIND_DOCUMENT],
        'ppt'  => [['application/vnd.ms-powerpoint'], self::KIND_DOCUMENT],
        'pptx' => [['application/vnd.openxmlformats-officedocument.presentationml.presentation', 'application/zip'], self::KIND_DOCUMENT],
        'odt'  => [['application/vnd.oasis.opendocument.text', 'application/zip'], self::KIND_DOCUMENT],

        // Audio
        'mp3'  => [['audio/mpeg'], self::KIND_AUDIO],
        'wav'  => [['audio/wav', 'audio/x-wav'], self::KIND_AUDIO],
        'ogg'  => [['audio/ogg', 'video/ogg', 'application/ogg'], self::KIND_AUDIO],
        'flac' => [['audio/flac', 'audio/x-flac'], self::KIND_AUDIO],
        'm4a'  => [['audio/mp4', 'audio/x-m4a'], self::KIND_AUDIO],

        // Video
        'mp4'  => [['video/mp4'], self::KIND_VIDEO],
        'webm' => [['video/webm'], self::KIND_VIDEO],
        'mov'  => [['video/quicktime'], self::KIND_VIDEO],
        'mkv'  => [['video/x-matroska'], self::KIND_VIDEO],

        // Archives
        'zip'  => [['application/zip', 'application/x-zip-compressed'], self::KIND_ARCHIVE],
        'gz'   => [['application/gzip', 'application/x-gzip'], self::KIND_ARCHIVE],
        'tgz'  => [['application/gzip', 'application/x-gzip'], self::KIND_ARCHIVE],
        'tar'  => [['application/x-tar'], self::KIND_ARCHIVE],
        '7z'   => [['application/x-7z-compressed'], self::KIND_ARCHIVE],
        'rar'  => [['application/vnd.rar', 'application/x-rar-compressed'], self::KIND_ARCHIVE],
    ];

    /** Display order for the browse page. */
    public const KINDS = [
        self::KIND_IMAGE,
        self::KIND_DOCUMENT,
        self::KIND_VIDEO,
        self::KIND_AUDIO,
        self::KIND_ARCHIVE,
        self::KIND_OTHER,
    ];

    private const LABELS = [
        self::KIND_IMAGE    => 'Images',
        self::KIND_DOCUMENT => 'Documents',
        self::KIND_AUDIO    => 'Audio',
        self::KIND_VIDEO    => 'Video',
        self::KIND_ARCHIVE  => 'Archives',
        self::KIND_OTHER    => 'Other',
    ];

    /** Fomantic icon per kind, used wherever we cannot show a thumbnail. */
    private const ICONS = [
        self::KIND_IMAGE    => 'file image outline',
        self::KIND_DOCUMENT => 'file alternate outline',
        self::KIND_AUDIO    => 'file audio outline',
        self::KIND_VIDEO    => 'file video outline',
        self::KIND_ARCHIVE  => 'file archive outline',
        self::KIND_OTHER    => 'file outline',
    ];

    public static function isAllowedExtension(string $extension): bool
    {
        return isset(self::TYPES[strtolower($extension)]);
    }

    /**
     * Does the sniffed MIME type match what the extension claims?
     *
     * Extension checks alone are not enough: the extension is attacker-supplied
     * and says nothing about the bytes. We sniff with finfo and require the two
     * to agree, so a PHP script renamed to .png is rejected.
     */
    public static function matchesExtension(string $extension, string $detectedMime): bool
    {
        $extension = strtolower($extension);
        if (!isset(self::TYPES[$extension])) {
            return false;
        }

        return in_array($detectedMime, self::TYPES[$extension][0], true);
    }

    public static function kindForExtension(string $extension): string
    {
        return self::TYPES[strtolower($extension)][1] ?? self::KIND_OTHER;
    }

    public static function mimeForExtension(string $extension): string
    {
        return self::TYPES[strtolower($extension)][0][0] ?? 'application/octet-stream';
    }

    public static function label(string $kind): string
    {
        return self::LABELS[$kind] ?? ucfirst($kind);
    }

    public static function icon(string $kind): string
    {
        return self::ICONS[$kind] ?? self::ICONS[self::KIND_OTHER];
    }

    /** @return string[] */
    public static function allowedExtensions(): array
    {
        return array_keys(self::TYPES);
    }

    /**
     * Only raster images we trust are ever served inline; everything else is
     * downloaded as an attachment. See the SVG note above.
     */
    public static function canPreviewInline(string $kind): bool
    {
        return $kind === self::KIND_IMAGE;
    }

    public static function formatSize(int $bytes): string
    {
        if ($bytes < 1024) {
            return $bytes . ' B';
        }

        $units = ['KB', 'MB', 'GB', 'TB'];
        $value = $bytes / 1024;
        $unit  = 0;

        while ($value >= 1024 && $unit < count($units) - 1) {
            $value /= 1024;
            $unit++;
        }

        return round($value, $value >= 10 ? 0 : 1) . ' ' . $units[$unit];
    }
}
