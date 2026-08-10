# FileShare

A small PHP file-upload service. Register, upload files of any supported type,
browse them grouped by kind, reorder and rename your own, and download.

No framework, no build step and no package manager — plain PHP on a stock
`php:8.3-apache` image, with state in SQLite.

## Running

```bash
docker compose up --build
```

Then open <http://localhost:8080>.

| Variable        | Default | Meaning                                    |
| --------------- | ------- | ------------------------------------------ |
| `APP_PORT`      | `8080`  | Host port to publish                       |
| `MAX_UPLOAD_MB` | `50`    | Per-file upload ceiling                    |

`MAX_UPLOAD_MB` must stay below `post_max_size` in `docker/php.ini` (68M). PHP
discards the request body before the script runs when that is exceeded, so a
higher app limit would never be reachable.

Data lives in two named volumes — `fileshare_data` (the SQLite database) and
`fileshare_uploads` (the stored bytes) — and survives `docker compose down`.
To wipe it:

```bash
docker compose down -v
```

## Layout

```
public/            document root — the only directory served over HTTP
  index.php        redirects to browse or login
  login/register/logout.php
  browse.php       file listing, grouped by kind
  upload.php       upload form
  profile.php      profile + reorderable list of your files
  download.php     the only route to stored bytes
  api.php          JSON endpoint: rename / delete / reorder
  partials/        shared header and footer
  assets/          app.css and the JS modules

src/               application code, not web-reachable
  bootstrap.php    paths, session config, service wiring
  Database.php     PDO/SQLite connection, pragmas, migrations
  schema.sql       the schema, applied once
  Files.php        the file index
  Users.php        the user records
  Auth.php         session authentication
  Csrf.php         per-session tokens
  Uploader.php     upload validation and ingest
  FileTypes.php    allowlist, kinds, icons, size formatting
  helpers.php      escaping, redirects, flashes

data/              fileshare.sqlite            (volume)
storage/uploads/   uploaded bytes              (volume)
docker/            Dockerfile, vhost, php.ini
```

## How it works

**Storage.** SQLite in WAL mode, so readers proceed while a writer holds the
write lock. Positions are assigned by the INSERT itself and ownership is a
predicate in the UPDATE/DELETE, so neither has a check-then-act window.
`busy_timeout` makes a second writer wait rather than fail with `SQLITE_BUSY`.

The schema is applied once on first connect, guarded by `PRAGMA user_version`.
There is no migration runner beyond that: bumping the schema means adding to
`schema.sql` and raising that version.

**Uploads.** A file must have an allowed extension *and* contents whose sniffed
MIME type agrees with it. It is stored outside the document root under a random
name at mode 0640, so the web server is never asked to interpret it.

**Downloads.** `download.php` is the only path to stored bytes. Raster images may
be served inline; everything else is forced to `attachment` with an opaque
content type, `nosniff`, and a sandbox CSP.

**Kinds.** A file's category is derived from the file itself — image, document,
audio, video, archive, other — rather than chosen by the uploader.

## Supported types

Images (jpg, jpeg, png, gif, webp, bmp), documents (pdf, txt, md, csv, rtf, doc,
docx, xls, xlsx, ppt, pptx, odt), audio (mp3, wav, ogg, flac, m4a), video (mp4,
webm, mov, mkv), archives (zip, gz, tgz, tar, 7z, rar).

SVG is deliberately excluded: it is a script-capable document, and serving one
inline from our own origin would be stored XSS.

## Known limitations

- SQLite handles one writer at a time. That is ample here, but a write-heavy
  deployment would queue on `busy_timeout`.
- Any logged-in user can view and download any file. There is no per-file
  visibility; ownership only governs rename, delete, and reorder.
- No rate limiting on login, so passwords are only as good as the ones chosen.
- No unit tests. `tests/smoke.sh` is an end-to-end check against a running
  stack, and `php -l` is the only static one.

## Testing

```bash
docker compose up --build -d
./tests/smoke.sh
```

27 checks through the real HTTP stack. It registers throwaway accounts and
uploads into the attached volume, so point it at a scratch stack;
`docker compose down -v` resets state between runs.
