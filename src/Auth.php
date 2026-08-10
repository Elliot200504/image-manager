<?php

declare(strict_types=1);

/**
 * Session-backed authentication.
 *
 * The old app treated a `logged_in=1` cookie as proof of identity alongside the
 * session, which meant anyone could set that cookie by hand and pass the
 * `$logged_in` check on every page. Identity now lives only in the session.
 */
final class Auth
{
    private const KEY = 'auth_user';

    public function __construct(private readonly Users $users)
    {
    }

    public function attempt(string $username, string $password): bool
    {
        if (!$this->users->verifyPassword($username, $password)) {
            return false;
        }

        // Re-issue the session id on privilege change so a session fixed by an
        // attacker before login does not become an authenticated one.
        session_regenerate_id(true);

        // Store the canonical spelling from storage, not what was typed.
        $user = $this->users->find($username);
        $_SESSION[self::KEY] = $user['username'] ?? $username;

        return true;
    }

    public function login(string $username): void
    {
        session_regenerate_id(true);
        $_SESSION[self::KEY] = $username;
    }

    public function logout(): void
    {
        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', [
                'expires'  => time() - 42000,
                'path'     => $params['path'],
                'domain'   => $params['domain'],
                'secure'   => $params['secure'],
                'httponly' => $params['httponly'],
                'samesite' => $params['samesite'] ?? 'Lax',
            ]);
        }

        session_destroy();
    }

    public function check(): bool
    {
        return $this->username() !== null;
    }

    public function username(): ?string
    {
        $username = $_SESSION[self::KEY] ?? null;

        return is_string($username) && $username !== '' ? $username : null;
    }

    /** @return array<string, mixed>|null */
    public function user(): ?array
    {
        $username = $this->username();

        return $username === null ? null : $this->users->find($username);
    }

    /** Send anonymous visitors to the login page. */
    public function requireLogin(): string
    {
        $username = $this->username();

        if ($username === null) {
            redirect('login.php');
        }

        // A session naming a user who no longer exists is not a valid identity.
        if (!$this->users->exists($username)) {
            $this->logout();
            redirect('login.php');
        }

        return $username;
    }

    /** The JSON-endpoint equivalent of requireLogin(). */
    public function requireLoginJson(): string
    {
        $username = $this->username();

        if ($username === null || !$this->users->exists($username)) {
            json_response(['ok' => false, 'error' => 'Not authenticated.'], 401);
        }

        return $username;
    }
}
