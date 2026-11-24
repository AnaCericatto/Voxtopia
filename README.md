# Voxtopia

[![tests](https://github.com/AnaCericatto/Voxtopia/actions/workflows/tests.yml/badge.svg)](https://github.com/AnaCericatto/Voxtopia/actions/workflows/tests.yml)

A web app to plan voxel builds in 2D and 3D, share them, and walk others through them step by step.
Work in progress: see [the design document](docs/Design%20Document.md).

**Stack:** Laravel, Inertia, React, TypeScript, PostgreSQL, Pest.

## Requirements

- PHP 8.4 or newer, and Composer
- Node 22 or newer
- Docker (runs PostgreSQL)

## Run it locally

```bash
docker compose up -d      # start PostgreSQL
composer setup            # install dependencies, create .env, app key, run migrations, build assets
composer dev              # start the app and the Vite dev server
```

Open http://localhost:8000.

PostgreSQL runs on `127.0.0.1:5432` with database `voxtopia` (development) and `voxtopia_testing` (tests),
user `voxtopia`, password `secret`. These are for local development only.

If port 5432 is already used by another PostgreSQL, change the left side of the port mapping in
`docker-compose.yml` (for example `"127.0.0.1:5433:5432"`) and set `DB_PORT=5433` in `.env`.

To delete all local database data and start over: `docker compose down -v`, then run the steps above again.

## Checks

```bash
composer test         # code style (Pint), static analysis (PHPStan), then the Pest tests
npm run check         # frontend lint and format
npm run types:check   # TypeScript
composer ci:check     # everything CI runs
```
