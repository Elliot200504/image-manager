<?php

declare(strict_types=1);

/**
 * The SQLite connection, and the pragmas that make it behave under concurrency.
 *
 * This replaces Storage, which serialised every write on a whole-file lock and
 * re-parsed the entire dataset on every page load. SQLite gives real concurrent
 * readers, indexed lookups, and atomic multi-statement writes.
 */
final class Database
{
    private PDO $pdo;

    public function __construct(private readonly string $path)
    {
        $this->ensureDirectory();

        $this->pdo = new PDO('sqlite:' . $this->path, null, null, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            // Real prepared statements, so a value can never be parsed as SQL.
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);

        // WAL lets readers proceed while a writer holds the write lock — the
        // whole point of moving off the flat file. It persists in the database
        // itself, but setting it every connect costs nothing and keeps a
        // restored or copied file correct.
        $this->pdo->exec('PRAGMA journal_mode = WAL');

        // Without this, two simultaneous writers make the loser fail instantly
        // with SQLITE_BUSY instead of waiting its turn.
        $this->pdo->exec('PRAGMA busy_timeout = 5000');

        // Off by default in SQLite; the schema's cascades do nothing without it.
        $this->pdo->exec('PRAGMA foreign_keys = ON');

        // With WAL, NORMAL is durable across application crashes and only risks
        // the last transactions on a full power loss. Worth it here.
        $this->pdo->exec('PRAGMA synchronous = NORMAL');

        $this->migrate();
    }

    public function pdo(): PDO
    {
        return $this->pdo;
    }

    /**
     * Run a callable inside a transaction, rolling back on any exception.
     *
     * @template T
     * @param callable(PDO): T $work
     * @return T
     */
    public function transaction(callable $work): mixed
    {
        // Nested calls would otherwise throw; join the outer transaction so a
        // repository method is safe to call from inside another one.
        if ($this->pdo->inTransaction()) {
            return $work($this->pdo);
        }

        $this->pdo->beginTransaction();

        try {
            $result = $work($this->pdo);
            $this->pdo->commit();

            return $result;
        } catch (Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    /**
     * Apply the schema once.
     *
     * user_version is a counter stored in the database header, so this costs a
     * single pragma read on a warm database rather than re-running DDL.
     */
    private function migrate(): void
    {
        $version = (int) $this->pdo->query('PRAGMA user_version')->fetchColumn();

        if ($version >= 1) {
            return;
        }

        $sql = file_get_contents(__DIR__ . '/schema.sql');
        if ($sql === false) {
            throw new RuntimeException('Cannot read schema.sql');
        }

        // DDL in SQLite is transactional, so a failure part-way leaves no
        // half-built schema behind.
        $this->pdo->beginTransaction();

        try {
            $this->pdo->exec($sql);
            $this->pdo->exec('PRAGMA user_version = 1');
            $this->pdo->commit();
        } catch (Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    private function ensureDirectory(): void
    {
        $dir = dirname($this->path);

        if (!is_dir($dir) && !@mkdir($dir, 0775, true) && !is_dir($dir)) {
            throw new RuntimeException("Cannot create database directory: {$dir}");
        }

        // WAL writes -wal and -shm siblings, so the directory has to be
        // writable, not just the database file.
        if (!is_writable($dir)) {
            throw new RuntimeException("Database directory is not writable: {$dir}");
        }
    }
}
