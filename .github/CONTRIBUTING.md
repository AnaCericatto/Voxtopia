# Contributing

## Workflow
- `main` is protected. Work on a short branch (`feat/...`, `fix/...`, `chore/...`, `docs/...`) and open a pull request.
- Commit messages follow [Conventional Commits](https://www.conventionalcommits.org/): `feat(editor): add undo`.
- CI must be green before merging.

## Before you push

composer test # Pint, Larastan, Pest
npm run check # lint and format (Vite+)
npm run types:check # TypeScript
npm test # Vitest

`composer lint` and `npm run check:fix` fix style problems automatically.

## Releases
Each milestone ends with an annotated tag (`v0.1.0`, ...) and an entry in `CHANGELOG.md`.