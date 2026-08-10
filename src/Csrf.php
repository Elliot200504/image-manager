<?php

declare(strict_types=1);

/**
 * Per-session CSRF tokens.
 *
 * The old app had none: any page on the internet could POST to
 * `documentupload.php` or `documentplacer.php` with the victim's session cookie
 * attached and upload, rename, or reorder on their behalf. Every state-changing
 * request now carries a token that a cross-origin page cannot read.
 */
final class Csrf
{
    private const SESSION_KEY = '_csrf';
    public const FIELD = '_token';

    /** The token for this session, minted on first use. */
    public static function token(): string
    {
        if (empty($_SESSION[self::SESSION_KEY])) {
            $_SESSION[self::SESSION_KEY] = random_id(32);
        }

        return $_SESSION[self::SESSION_KEY];
    }

    /** A ready-made hidden input for forms. */
    public static function field(): string
    {
        return '<input type="hidden" name="' . self::FIELD . '" value="' . e(self::token()) . '">';
    }

    /**
     * Constant-time comparison against the session token.
     *
     * hash_equals rather than `===` so the comparison does not leak the token
     * prefix through timing.
     */
    public static function check(?string $candidate): bool
    {
        $expected = $_SESSION[self::SESSION_KEY] ?? '';

        if ($expected === '' || $candidate === null || $candidate === '') {
            return false;
        }

        return hash_equals($expected, $candidate);
    }

    /**
     * Guard a state-changing request. Ends the request with 419 if the token is
     * absent or wrong, so a missed token fails loudly rather than silently
     * writing.
     *
     * Accepts the token from the form field or the X-CSRF-Token header, the
     * latter for the JSON endpoints.
     */
    public static function verify(bool $json = false): void
    {
        $candidate = $_POST[self::FIELD]
            ?? $_SERVER['HTTP_X_CSRF_TOKEN']
            ?? null;

        if (self::check(is_string($candidate) ? $candidate : null)) {
            return;
        }

        if ($json) {
            json_response(['ok' => false, 'error' => 'Invalid or expired session token.'], 419);
        }

        http_response_code(419);
        exit('Invalid or expired session token. Go back, reload the page, and try again.');
    }
}
