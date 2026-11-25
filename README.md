# Voxtopia

[![tests](https://github.com/AnaCericatto/Voxtopia/actions/workflows/tests.yml/badge.svg)](https://github.com/AnaCericatto/Voxtopia/actions/workflows/tests.yml)

A web app to plan voxel builds in 2D and 3D, share them, and walk others through them step by step.

**Status:** early development. Milestone M0 (foundations) is done: accounts, layout, database, tests and CI.
The editor comes next. See the [design document](docs/Design%20Document.md) for the plan and the milestones.

**Stack:** Laravel, Inertia, React, TypeScript, Tailwind, PostgreSQL, Pest, Vitest.

## Requirements

- PHP 8.4 or newer, and Composer
- Node 22 or newer
- Docker (runs PostgreSQL and Mailpit)

## Run it locally

```bash
docker compose up -d      # start PostgreSQL and Mailpit
composer setup            # install dependencies, create .env, app key, run migrations, build assets
php artisan db:seed       # optional: create a test user
composer dev              # start the app and the Vite dev server
```

| What                                                            | Where                                                                                                                   |
| --------------------------------------------------------------- | ----------------------------------------------------------------------------------------------------------------------- |
| The app                                                         | http://localhost:8000                                                                                                   |
| Mailpit (fake inbox: verification and reset emails arrive here) | http://localhost:8025                                                                                                   |
| PostgreSQL                                                      | `127.0.0.1:5432`, databases `voxtopia` (development) and `voxtopia_testing` (tests), user `voxtopia`, password `secret` |

The official pieces are data, not demo data. After deploying, run:

    php artisan db:seed --class=OfficialPiecesSeeder --force

It is idempotent, so run it again whenever the palette changes.

The seeder creates the user `test@example.com` with the password `password`.
The database settings are for local development only.

If port 5432 is already used by another PostgreSQL, change the left side of the port mapping in
`docker-compose.yml` (for example `"127.0.0.1:5433:5432"`) and set `DB_PORT=5433` in `.env`.

To delete all local data and start over: `docker compose down -v`, then run the steps above again.

## Checks

```bash
composer test         # code style (Pint), static analysis (Larastan), then the Pest tests
npm run check         # frontend lint and format (Vite+)
npm run types:check   # TypeScript
npm test              # frontend unit tests (Vitest)
composer ci:check     # everything CI runs
```

`composer lint` and `npm run check:fix` fix style problems automatically.

## Contributing

`main` is protected: work on a short branch and open a pull request. See [CONTRIBUTING.md](CONTRIBUTING.md).
Changes per release are listed in [CHANGELOG.md](CHANGELOG.md).

## License

The code is released under the [MIT License](LICENSE). This covers the source code only. Images and pieces uploaded by users belong to their authors.
