<?php

declare(strict_types=1);

/**
 * The user records in data/users.json.
 *
 * Usernames are matched case-insensitively so "Elliot" and "elliot" cannot both
 * register and then race each other for the same profile.
 */
final class Users
{
    public function __construct(private readonly Storage $storage)
    {
    }

    /** @return array<int, array<string, mixed>> */
    public function all(): array
    {
        return $this->storage->read();
    }

    /** @return array<string, mixed>|null */
    public function find(string $username): ?array
    {
        foreach ($this->storage->read() as $user) {
            if (isset($user['username']) && $this->same($user['username'], $username)) {
                return $user;
            }
        }

        return null;
    }

    public function exists(string $username): bool
    {
        return $this->find($username) !== null;
    }

    /**
     * Create a user. Returns false if the name is taken.
     *
     * The existence check happens inside the lock: checking first and then
     * writing would let two simultaneous registrations both pass the check.
     */
    public function create(string $username, string $password): bool
    {
        return $this->storage->mutate(function (array &$users) use ($username, $password): bool {
            foreach ($users as $user) {
                if (isset($user['username']) && $this->same($user['username'], $username)) {
                    return false;
                }
            }

            $users[] = [
                'username'    => $username,
                'password'    => password_hash($password, PASSWORD_DEFAULT),
                'description' => '',
                'avatar'      => null,
                'created_at'  => time(),
            ];

            return true;
        });
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
     * Apply changes to a user's profile fields.
     *
     * @param array<string, mixed> $changes
     */
    public function update(string $username, array $changes): void
    {
        // Only fields a user is allowed to edit. Without this an extra POST
        // parameter could overwrite `password` or `username`.
        $editable = ['description', 'avatar'];

        $this->storage->mutate(function (array &$users) use ($username, $changes, $editable): void {
            foreach ($users as &$user) {
                if (isset($user['username']) && $this->same($user['username'], $username)) {
                    foreach ($editable as $field) {
                        if (array_key_exists($field, $changes)) {
                            $user[$field] = $changes[$field];
                        }
                    }
                    break;
                }
            }
            unset($user);
        });
    }

    private function same(string $a, string $b): bool
    {
        return mb_strtolower($a) === mb_strtolower($b);
    }
}
