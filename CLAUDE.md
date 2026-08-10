# CLAUDE.md

Guidance for Claude Code when working in this repository.

## Project

`fileshare` is a small PHP file-upload service: register/login, upload files of
any supported type, browse them grouped by kind, reorder and rename your own,
download. No framework, no build step, no package manager, no database — plain
PHP on `php:8.3-apache`, with state in flat JSON files.

It began as an images-only app (`image-manager`) and was rewritten. The git
history before the rewrite reflects that older design; do not treat it as
current.

## Running

Docker is the only supported way to run this:

```bash
docker compose up --build     # http://localhost:8080
```

There are no tests, no linter, and no build step. `php -l <file>` is the only
automated check — run it on every PHP file you touch.

## Architecture

`public/` is the document root and the **only** directory served over HTTP.
`src/`, `data/`, and `storage/` sit beside it, unreachable. This is load-bearing:
it is what stops an uploaded file from being requested and interpreted.

- **`src/bootstrap.php`** — every entry point starts by requiring this. Defines
  paths, configures the session cookie, and constructs `$storage`, `$users`,
  `$files`, `$auth`, `$uploader` into scope. There is no autoloader by design.
- **`src/Storage.php`** — a JSON file of records. `mutate()` holds
  `flock(LOCK_EX)` across read-modify-write. **All writes must go through it**;
  a bare `file_put_contents` reintroduces the lost-update bug the class exists
  to prevent.
- **`src/Files.php` / `src/Users.php`** — repositories over those files.
  Ownership is re-checked *inside* the lock, not by the caller.
- **`src/Uploader.php`** — the only path by which bytes enter storage.
- **`src/FileTypes.php`** — the allowlist. Adding a type means adding it here,
  with its permitted MIME types.

## Data storage (flat files — no database)

- **`data/users.json`** — `[{ username, password (hashed), description, avatar, created_at }]`
  where `avatar` is a `file_id`.
- **`data/files.json`** — `[{ file_id, owner, stored_name, original_name, title,
  extension, mime, kind, size, width, height, position, uploaded_at }]`
  - `stored_name` is the random name on disk; `original_name` is for display.
  - `file_id` is `f_<random hex>` — **not** sequential. The old `Bild_ID_<n>`
    scheme collided after any delete.

Both live in Docker named volumes and are gitignored. Treat them as runtime
state, never as fixtures.

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
