# FileShare

A small PHP file-upload service. Register, upload files of any supported type,
browse them grouped by kind, reorder and rename your own, and download.

No framework, no build step and no package manager, plain PHP on a stock
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

Data lives in two named volumes `fileshare_data` (the SQLite database) and
`fileshare_uploads` (the stored bytes) — and survives `docker compose down`.
To wipe it:

```bash
docker compose down -v
```
