<?php

declare(strict_types=1);

/**
 * The user records, in SQLite.
 *
 * Case-insensitive matching is now the primary key's own COLLATE NOCASE rather
 * than a manual comparison repeated at each call site.
 */
final class Users
{
    public function __construct(private readonly Database $db)
    {
    }

    /** @return array<int, array<string, mixed>> */
    public function all(): array
    {
        return $this->db->pdo()
            ->query('SELECT * FROM users ORDER BY username')
            ->fetchAll();
    }

    /** @return array<string, mixed>|null */
    public function find(string $username): ?array
    {
        $stmt = $this->db->pdo()->prepare('SELECT * FROM users WHERE username = ?');
        $stmt->execute([$username]);

        $row = $stmt->fetch();

        return $row === false ? null : $row;
    }

    public function exists(string $username): bool
    {
        $stmt = $this->db->pdo()->prepare('SELECT 1 FROM users WHERE username = ?');
        $stmt->execute([$username]);

        return $stmt->fetchColumn() !== false;
    }

    /**
     * Create a user. Returns false if the name is taken.
     *
     * Uniqueness is enforced by the primary key, so a duplicate is a constraint
     * violation rather than something a prior SELECT could have missed — two
     * simultaneous registrations cannot both succeed.
     */
    public function create(string $username, string $password): bool
    {
        $stmt = $this->db->pdo()->prepare(
            'INSERT INTO users (username, password, description, avatar, created_at)
             VALUES (?, ?, \'\', NULL, ?)'
        );

        try {
            $stmt->execute([
                $username,
                password_hash($password, PASSWORD_DEFAULT),
                time(),
            ]);
        } catch (PDOException $e) {
            // 23000 is an integrity constraint violation — here, the username
            // primary key. Anything else is a real fault and must not be
            // reported to the user as "name taken".
            if (($e->errorInfo[0] ?? '') === '23000') {
                return false;
            }

            throw $e;
        }

        return true;
    }

    public function verifyPassword(string $username, string $password): bool
    {
        $user = $this->find($username);

        if ($user === null || !isset($user['password']) || !is_string($user['password'])) {
            // Hash anyway so a missing user costs the same time as a wrong
            // password — otherwise response timing enumerates valid usernames.
            password_verify($password, '$2y$12$' . str_repeat('.', 53));

            return false;
        }

        return password_verify($password, $user['password']);
    }

    /**
     * Apply changes to a user's editable profile fields.
     *
     * The allowlist is what stops an extra POST parameter from reaching
     * `password` or `username`.
     *
     * @param array<string, mixed> $changes
     */
    public function update(string $username, array $changes): void
    {
        $editable = ['description', 'avatar'];
        $set      = [];
        $params   = [];

        foreach ($editable as $field) {
            if (array_key_exists($field, $changes)) {
                // Column names come from the hardcoded list above, never from
                // the caller's keys, so this cannot become SQL injection.
                $set[]           = $field . ' = :' . $field;
                $params[$field]  = $changes[$field];
            }
        }

        if ($set === []) {
            return;
        }

        $params['username'] = $username;

        $stmt = $this->db->pdo()->prepare(
            'UPDATE users SET ' . implode(', ', $set) . ' WHERE username = :username'
        );
        $stmt->execute($params);
    }
}
