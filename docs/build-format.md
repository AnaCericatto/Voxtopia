# Build data format, version 1

The `data` column of `builds` (jsonb) and the `data` field sent by the editor. Steps are not part of it: they live in
`build_steps` (ADR 0011) and travel next to `data` in the save payload as `steps: [{ title, note }]`.

​```json
{
  "version": 1,
  "size": { "w": 32, "h": 16, "d": 32 },
  "palette": ["01j...", "01j..."],
  "blocks": [[0, 0, 0, 0, 0, 0], [1, 0, 0, 1, 2, 0]]
}
​```

- `size`: the build's own grid. 1 to 64 wide (`w`), 1 to 32 high (`h`), 1 to 64 deep (`d`). Whole numbers.
- `palette`: up to 32 different piece ids (lowercase ULIDs). Pieces of any kind are accepted and count as one cell until shapes arrive (M5). A palette entry may be unused.
- `blocks`: up to 20,000, each `[x, y, z, paletteIndex, rotation, step]`, whole numbers only. The order has no meaning.
  - `x` 0..w-1 (left to right), `y` 0..h-1 (bottom to top, the layer), `z` 0..d-1 (front to back).
  - `rotation` 0..3 (quarter turns). `step` is a 0-based index into the build's steps.
  - Two blocks cannot share a cell.
- The server derives `block_count` and `build_pieces` from the blocks and never trusts the client for them.

Validation lives twice: `App\Support\Builds\BuildDataValidator` (PHP, the final judge) and the TypeScript validator (M1.7).
Both run `tests/fixtures/build-validation.json` and report the same codes (`App\Enums\BuildDataError`), in block order, at most 10.
Only `palette_piece_unavailable` is PHP-only, because it needs the database.