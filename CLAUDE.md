# CLAUDE.md

Guidance for Claude Code when working in this repository.

## Project

`image-manager` is a small PHP web app for uploading, categorizing, ordering, and
browsing images. Users register/login, upload images with a title + type, and browse
them in a gallery grouped by type. There is no framework, no build step, and no
package manager — it's plain PHP served directly, plus jQuery-era frontend JS.

## Running

Served by a PHP-capable web server from the repo root (deployed under `/var/www/html`).
For local work, the built-in server is enough:

```bash
php -S localhost:8000        # then open http://localhost:8000/index.php
```

There are no tests, no linters, and no build/install commands. Edit a file and reload.

## Architecture

Each `*.php` file in the root is both an endpoint and (usually) a full HTML page.
`index.php` is the entry point: it gates on the session and redirects to `login.php`
or `home.php`.

- **`menu.php`** — shared header/nav, `include`d by most pages. Loads CDN assets
  (Fomantic UI 2.8.8, jQuery 3.7.1, SortableJS) plus local CSS/JS.
- **`login.php` / `register.php` / `logout.php`** — auth. Sessions via `$_SESSION`,
  plus a `logged_in` cookie. Passwords hashed with `password_hash`.
- **`documentupload.php`** — image upload form + POST handler. Also handles a JSON
  `action: rename` request.
- **`documentplacer.php`** — JSON API for reordering images (drag-and-drop) and
  renaming. Reads/writes `documents.json`.
- **`home.php`** — computes type groupings/counts in PHP, passes them to the frontend
  as `window.*` globals, then hands off to `assets/js/spa.js`.
- **`profile.php`** — profile page; per-user description + profile picture.

### Frontend JS (`assets/js/`)
- `spa.js` — client-side rendering/navigation for the home/types/gallery views,
  driven by the `window.imagesByType / typeCounts / types / top3types` globals.
- `gallery.js` — `ImageGallery` class: fullscreen modal image viewer with prev/next.
- `main.js` — SortableJS wiring for drag-to-reorder; POSTs new order to
  `documentplacer.php`.

## Data storage (flat files — no database)

State lives in JSON files at the repo root, written with `file_put_contents`:

- **`users.json`** — `[{ username, password (hashed), description?, profilePic? }]`
- **`documents.json`** — `[{ Bild_ID, order, filename, source, username, title, type }]`
  - `source` is the stored unique filename in `uploads/`; `filename` is the original
    display name; `Bild_ID` is `Bild_ID_<n>`.

Uploaded files go to `uploads/`. **`*.json`, `uploads/`, and `.env` are gitignored** —
do not commit them; treat `users.json`/`documents.json` as runtime data, not fixtures.

## Conventions & cautions

- Match the existing style: procedural PHP with inline HTML, Fomantic UI classes,
  heavy inline `style="..."` attributes. Some UI text is in Swedish (e.g. "Bild" =
  image) — keep existing labels as-is unless asked.
- Image type values are a fixed set: `Other, Animals, People, Architecture,
  Technology, Clothing` (see the `<select>` in `documentupload.php`). Type cards map
  to `assets/images/<type lowercased>.png`.
- Concurrent writes to the JSON files are not locked; keep that in mind for any
  read-modify-write change.
- This is a learning/demo app — known rough edges include unvalidated session reuse
  and no CSRF protection. Don't introduce new auth/upload paths without flagging the
  security implications.
