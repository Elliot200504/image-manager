<?php

declare(strict_types=1);

require_once __DIR__ . '/../src/bootstrap.php';

/** @var Auth $auth */
/** @var Files $files */

// Uploads live outside the document root, so this script is the only route to
// them. That is the point: the web server will never be asked to interpret an
// uploaded file, it only ever gets streamed as bytes.
$auth->requireLogin();

$fileId = (string) ($_GET['id'] ?? '');
$record = $fileId !== '' ? $files->find($fileId) : null;

if ($record === null) {
    http_response_code(404);
    exit('File not found.');
}

$path = $files->pathFor((string) ($record['stored_name'] ?? ''));

if ($path === null || !is_file($path) || !is_readable($path)) {
    http_response_code(404);
    exit('File not found.');
}

$kind = (string) ($record['kind'] ?? FileTypes::KIND_OTHER);

// Inline rendering is limited to raster images. Anything else — HTML, SVG, a
// PDF with embedded script — would run in our origin if a browser rendered it
// inline, so it is always served as a download.
$wantsInline = isset($_GET['inline']) && FileTypes::canPreviewInline($kind);
$disposition = $wantsInline ? 'inline' : 'attachment';

// Serve the declared type for images we vetted at upload time; force an opaque
// type for everything else so a browser never sniffs its way into rendering.
$contentType = $wantsInline
    ? FileTypes::mimeForExtension((string) ($record['extension'] ?? ''))
    : 'application/octet-stream';

$downloadName = sanitize_filename((string) ($record['original_name'] ?? 'download'));

// RFC 6266: an ASCII fallback plus a UTF-8 form, so non-ASCII names survive.
$asciiName = preg_replace('/[^\x20-\x7E]/', '_', $downloadName) ?? 'download';

header('Content-Type: ' . $contentType);
header('Content-Length: ' . (string) filesize($path));
header('X-Content-Type-Options: nosniff');
header('Content-Security-Policy: default-src \'none\'; sandbox');
header(sprintf(
    '%s; filename="%s"; filename*=UTF-8\'\'%s',
    'Content-Disposition: ' . $disposition,
    str_replace('"', '', $asciiName),
    rawurlencode($downloadName)
));

// Private: these are user files behind a login and must not be cached by a
// shared proxy where another user could be served them.
header('Cache-Control: private, max-age=0, must-revalidate');

// Drop any buffering so a large file streams instead of being built in memory.
while (ob_get_level() > 0) {
    ob_end_clean();
}

readfile($path);
