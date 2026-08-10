# CLAUDE.md

Guidance for Claude Code when working in this repository.

## Project

`fileshare` is a small PHP file-upload service: register/login, upload files of
any supported type, browse them grouped by kind, reorder and rename your own,
download. No framework, no build step and no package manager — plain PHP on
`php:8.3-apache`, with state in SQLite.

It began as an images-only app (`image-manager`) and was rewritten. The git
history before the rewrite reflects that older design; do not treat it as
current.

## Running

Docker is the only supported way to run this:

```bash
docker compose up --build     # http://localhost:8080
```

No linter and no build step. `php -l <file>` on every PHP file you touch, and
`./tests/smoke.sh` against a running stack — 27 checks over real HTTP, covering
the security invariants below plus concurrency and reordering. Reset with
`docker compose down -v` between runs; re-running without a reset fails at
registration because the usernames already exist.

## Architecture

`public/` is the document root and the **only** directory served over HTTP.
`src/`, `data/`, and `storage/` sit beside it, unreachable. This is load-bearing:
it is what stops an uploaded file from being requested and interpreted.

- **`src/bootstrap.php`** — every entry point starts by requiring this. Defines
  paths, configures the session cookie, and constructs `$db`, `$users`,
  `$files`, `$auth`, `$uploader` into scope. There is no autoloader by design.
- **`src/Database.php`** — the PDO/SQLite connection and its pragmas (WAL,
  `busy_timeout`, `foreign_keys`, `synchronous`). Applies `schema.sql` once,
  guarded by `PRAGMA user_version`; bumping the schema means editing that file
  and raising the version.
- **`src/Files.php` / `src/Users.php`** — repositories. **Ownership belongs in
  the WHERE clause**, not in a separate SELECT beforehand — that is what removes
  the check-then-act window. Likewise `position` is assigned by the INSERT
  (`COALESCE(MAX(position),0)+1`), never read and then written.
- **`src/Uploader.php`** — the only path by which bytes enter storage.
- **`src/FileTypes.php`** — the allowlist. Adding a type means adding it here,
  with its permitted MIME types.

## Data storage (SQLite)

`data/fileshare.sqlite`, schema in `src/schema.sql`:

- **`users`** — `username` (PK, `COLLATE NOCASE`), `password` (hashed),
  `description`, `avatar` → `files.file_id` `ON DELETE SET NULL`, `created_at`.
  The NOCASE primary key is what enforces case-insensitive uniqueness; do not
  reintroduce manual `strtolower` comparisons.
- **`files`** — `file_id` (PK), `owner` → `users.username` `ON DELETE CASCADE`,
  `stored_name` (UNIQUE), `original_name`, `title`, `extension`, `mime`, `kind`,
  `size`, `width`, `height`, `position`, `uploaded_at`.
  - `stored_name` is the random name on disk; `original_name` is for display.
  - `file_id` is `f_<random hex>` — **not** sequential. The old `Bild_ID_<n>`
    scheme collided after any delete.

The database lives in a Docker named volume and is gitignored. Treat it as
runtime state, never as a fixture. `foreign_keys` is OFF by default in SQLite
and is enabled per-connection in `Database` — the cascades above do nothing
without it.

## Security invariants

These are not incidental; the rewrite exists largely to establish them. Do not
regress one without saying so explicitly.

- **Uploads are never web-reachable.** They live outside the document root and
  are served only by `download.php`. Never add a route that serves
  `storage/uploads` directly.
- **Extension *and* content must agree.** `Uploader` sniffs with `finfo` and
  requires the sniffed MIME to match the extension's allowlist entry.
- **Only raster images may be served inline.** Everything else gets
  `Content-Disposition: attachment` and an opaque content type. SVG is excluded
  from the allowlist entirely — it is script-capable.
- **Every state-changing request carries a CSRF token.** `Csrf::verify()` in
  page handlers, `Csrf::verify(json: true)` in `api.php`. Logout is a POST.
- **Identity lives only in the session.** There is no "logged in" cookie; the
  old one was forgeable by hand.
- **Never build HTML from user input by interpolation.** In PHP use `e()`; in JS
  build nodes and assign `textContent`. A stored XSS via upload titles has been
  shipped in this codebase twice — once in the original gallery, once in a fix
  that covered only the grid and missed the modal.

## Conventions

- `declare(strict_types=1);` at the top of every PHP file.
- Procedural page handlers, small final classes underneath. Logic goes in
  `src/`; `public/` files handle the request and render.
- Escape with `e()` at the point of output, not at the point of storage.
- CSS lives in `public/assets/css/app.css` and uses the tokens at the top of it.
  No inline `style="..."` attributes except for computed values such as
  `--ratio`.
- Fomantic UI is loaded from a CDN and its component styles frequently need
  `!important` to override — that is expected in `app.css`, not a smell.
- Commit one file per commit, with a message explaining *why*.

## Cautions

- `MAX_UPLOAD_MB` (compose) must stay below `post_max_size` (`docker/php.ini`).
  Above it, PHP discards the body before the script runs and the app's own limit
  and error message become unreachable.
- Any logged-in user can download any file. Ownership governs only rename,
  delete, and reorder. If per-file visibility is ever needed, `download.php` is
  where it goes.
- There is no rate limiting on login.
