<?php

declare(strict_types=1);

/**
 * Escape for HTML output.
 *
 * Named `e()` because it is used on essentially every interpolation in the
 * templates, and a short name makes the ones that are *missing* easier to spot.
 */
function e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** Send a redirect and stop. Always exits — never returns. */
function redirect(string $path): never
{
    header('Location: ' . $path);
    exit;
}

/** Emit a JSON response and stop. */
function json_response(array $payload, int $status = 200): never
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * Queue a one-shot message for the next rendered page.
 *
 * @param 'success'|'error'|'info' $type
 */
function flash(string $type, string $message): void
{
    $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
}

/**
 * Drain the queued messages. Reading clears them, so a refresh does not
 * re-display the same banner.
 *
 * @return array<int, array{type: string, message: string}>
 */
function take_flashes(): array
{
    $flashes = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);

    return $flashes;
}

/**
 * A URL-safe random identifier.
 *
 * `uniqid()` — used by the old upload path — is derived from the clock, so
 * stored filenames were guessable. These are not.
 */
function random_id(int $bytes = 16): string
{
    return bin2hex(random_bytes($bytes));
}

/**
 * Reduce a user-supplied filename to something safe to store and to echo back
 * in a Content-Disposition header.
 *
 * Strips any directory component, control characters, and quotes; collapses the
 * rest. The result is only ever used as a *display* name — the bytes on disk are
 * keyed by a random id — but it still must not be able to escape a directory or
 * break out of a header.
 */
function sanitize_filename(string $name): string
{
    $name = basename(str_replace('\\', '/', $name));
    $name = preg_replace('/[\x00-\x1F\x7F"\']+/u', '', $name) ?? '';
    $name = trim($name, ". \t\n\r\0\x0B");

    if ($name === '') {
        $name = 'file';
    }

    return mb_substr($name, 0, 180);
}

/** Human-readable "3 minutes ago" for timestamps. */
function time_ago(int $timestamp): string
{
    $seconds = time() - $timestamp;

    if ($seconds < 60) {
        return 'just now';
    }

    $units = [
        ['year',   31556952],
        ['month',   2629746],
        ['week',     604800],
        ['day',       86400],
        ['hour',       3600],
        ['minute',       60],
    ];

    foreach ($units as [$label, $length]) {
        if ($seconds >= $length) {
            $count = (int) floor($seconds / $length);

            return $count . ' ' . $label . ($count === 1 ? '' : 's') . ' ago';
        }
    }

    return 'just now';
}
