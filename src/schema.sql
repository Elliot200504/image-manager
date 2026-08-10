-- FileShare schema.
--
-- Applied once by Database::migrate(), guarded by PRAGMA user_version.

CREATE TABLE IF NOT EXISTS users (
    -- COLLATE NOCASE makes the primary key itself case-insensitive, so
    -- "Elliot" and "elliot" cannot both exist. The JSON version enforced this
    -- with a manual scan that had to be repeated at every call site.
    username    TEXT PRIMARY KEY COLLATE NOCASE,
    password    TEXT NOT NULL,
    description TEXT NOT NULL DEFAULT '',
    avatar      TEXT REFERENCES files(file_id) ON DELETE SET NULL,
    created_at  INTEGER NOT NULL
);

CREATE TABLE IF NOT EXISTS files (
    file_id       TEXT PRIMARY KEY,
    owner         TEXT NOT NULL REFERENCES users(username) ON DELETE CASCADE,
    stored_name   TEXT NOT NULL UNIQUE,
    original_name TEXT NOT NULL,
    title         TEXT NOT NULL,
    extension     TEXT NOT NULL,
    mime          TEXT NOT NULL,
    kind          TEXT NOT NULL,
    size          INTEGER NOT NULL,
    -- Images only; null for every other kind.
    width         INTEGER,
    height        INTEGER,
    position      INTEGER NOT NULL,
    uploaded_at   INTEGER NOT NULL
);

-- Both list views order by position within a scope, so the index covers the
-- sort as well as the filter and SQLite never has to sort in memory.
CREATE INDEX IF NOT EXISTS idx_files_owner ON files (owner, position);
CREATE INDEX IF NOT EXISTS idx_files_kind  ON files (kind, position);
CREATE INDEX IF NOT EXISTS idx_files_pos   ON files (position);
