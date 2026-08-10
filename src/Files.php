<?php

declare(strict_types=1);

/**
 * The file index, in SQLite.
 *
 * The public API is unchanged from the JSON-backed version, so no page or
 * frontend code needed touching — that separation is why persistence lives in
 * its own class.
 */
final class Files
{
    public function __construct(
        private readonly Database $db,
        private readonly string $uploadPath,
    ) {
    }

    /** @return array<int, array<string, mixed>> */
    public function all(): array
    {
        return $this->db->pdo()
            ->query('SELECT * FROM files ORDER BY position, file_id')
            ->fetchAll();
    }

    /** @return array<int, array<string, mixed>> */
    public function forOwner(string $owner): array
    {
        $stmt = $this->db->pdo()->prepare(
            'SELECT * FROM files WHERE owner = ? ORDER BY position, file_id'
        );
        $stmt->execute([$owner]);

        return $stmt->fetchAll();
    }

    /** @return array<string, mixed>|null */
    public function find(string $fileId): ?array
    {
        $stmt = $this->db->pdo()->prepare('SELECT * FROM files WHERE file_id = ?');
        $stmt->execute([$fileId]);

        $row = $stmt->fetch();

        return $row === false ? null : $row;
    }

    /**
     * All files grouped by kind, in FileTypes::KINDS order, skipping empty
     * kinds so the browse page renders no heading for nothing.
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
        $rows = $this->db->pdo()
            ->query('SELECT kind, COUNT(*) AS n FROM files GROUP BY kind')
            ->fetchAll();

        $counts = [];
        foreach ($rows as $row) {
            $counts[(string) $row['kind']] = (int) $row['n'];
        }

        return $counts;
    }

    public function totalSize(): int
    {
        return (int) $this->db->pdo()
            ->query('SELECT COALESCE(SUM(size), 0) FROM files')
            ->fetchColumn();
    }

    /**
     * Insert a record, assigning the next position.
     *
     * The position is computed by the INSERT itself rather than read first and
     * written second, so two concurrent uploads cannot be handed the same slot.
     *
     * @param array<string, mixed> $record
     * @return array<string, mixed> the stored record, with its position filled in
     */
    public function add(array $record): array
    {
        $sql = 'INSERT INTO files (
                    file_id, owner, stored_name, original_name, title,
                    extension, mime, kind, size, width, height,
                    position, uploaded_at
                )
                SELECT :file_id, :owner, :stored_name, :original_name, :title,
                       :extension, :mime, :kind, :size, :width, :height,
                       COALESCE(MAX(position), 0) + 1, :uploaded_at
                FROM files';

        $stmt = $this->db->pdo()->prepare($sql);
        $stmt->execute([
            'file_id'       => $record['file_id'],
            'owner'         => $record['owner'],
            'stored_name'   => $record['stored_name'],
            'original_name' => $record['original_name'],
            'title'         => $record['title'],
            'extension'     => $record['extension'],
            'mime'          => $record['mime'],
            'kind'          => $record['kind'],
            'size'          => $record['size'],
            'width'         => $record['width'],
            'height'        => $record['height'],
            'uploaded_at'   => $record['uploaded_at'],
        ]);

        return $this->find((string) $record['file_id']) ?? $record;
    }

    /**
     * Retitle a file. The ownership test is part of the UPDATE, so there is no
     * window between checking and writing.
     */
    public function rename(string $fileId, string $owner, string $title): bool
    {
        $stmt = $this->db->pdo()->prepare(
            'UPDATE files SET title = ? WHERE file_id = ? AND owner = ?'
        );
        $stmt->execute([$title, $fileId, $owner]);

        return $stmt->rowCount() > 0;
    }

    /**
     * Delete a record and its bytes.
     *
     * The row goes first, inside a transaction: an orphaned file on disk is
     * harmless, whereas a row pointing at deleted bytes renders as a broken
     * tile. The unlink happens after commit so a rollback cannot leave the row
     * present but the file gone.
     */
    public function delete(string $fileId, string $owner): bool
    {
        $removed = $this->db->transaction(function (PDO $pdo) use ($fileId, $owner): ?array {
            $stmt = $pdo->prepare('SELECT * FROM files WHERE file_id = ? AND owner = ?');
            $stmt->execute([$fileId, $owner]);
            $row = $stmt->fetch();

            if ($row === false) {
                return null;
            }

            $delete = $pdo->prepare('DELETE FROM files WHERE file_id = ? AND owner = ?');
            $delete->execute([$fileId, $owner]);

            return $delete->rowCount() > 0 ? $row : null;
        });

        if ($removed === null) {
            return false;
        }

        $path = $this->pathFor((string) ($removed['stored_name'] ?? ''));
        if ($path !== null && is_file($path)) {
            @unlink($path);
        }

        return true;
    }

    /**
     * Apply a new ordering.
     *
     * Takes ids only — the endpoint this descends from accepted whole records
     * from the client and wrote them back, so a POST could rewrite any field.
     * The owner predicate means ids the caller does not own update nothing.
     *
     * @param string[] $orderedIds
     */
    public function reorder(array $orderedIds, string $owner): void
    {
        $this->db->transaction(function (PDO $pdo) use ($orderedIds, $owner): void {
            $stmt = $pdo->prepare(
                'UPDATE files SET position = ? WHERE file_id = ? AND owner = ?'
            );

            $position = 0;
            foreach ($orderedIds as $fileId) {
                $stmt->execute([++$position, $fileId, $owner]);
            }

            // Anything the request did not mention keeps its relative order,
            // after the rows it did.
            $rest = $pdo->prepare(
                'UPDATE files SET position = position + :offset
                 WHERE owner = :owner AND file_id NOT IN (' . $this->placeholders(count($orderedIds)) . ')'
            );

            $params = ['offset' => $position, 'owner' => $owner];
            foreach (array_values($orderedIds) as $i => $fileId) {
                $params['id' . $i] = $fileId;
            }
            $rest->execute($params);
        });
    }

    /**
     * Absolute path for a stored name, or null if it is not a name we could
     * have written.
     *
     * Defence in depth: if a row were ever tampered with, a stored_name of
     * "../../etc/passwd" would otherwise become a readable download.
     */
    public function pathFor(string $storedName): ?string
    {
        if (!preg_match('/^[a-f0-9]{32,64}\.[a-z0-9]{1,5}$/', $storedName)) {
            return null;
        }

        return $this->uploadPath . '/' . $storedName;
    }

    /** Named placeholders for a NOT IN list; ':id0, :id1, …' (or NULL if empty). */
    private function placeholders(int $count): string
    {
        if ($count === 0) {
            // "NOT IN (NULL)" is never true, which would skip every row — but
            // with no ids given there is nothing to push down, so an
            // always-false predicate is exactly right.
            return 'NULL';
        }

        return implode(', ', array_map(static fn (int $i) => ':id' . $i, range(0, $count - 1)));
    }
}
