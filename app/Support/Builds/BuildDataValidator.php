<?php

namespace App\Support\Builds;

use App\Enums\BuildDataError;

/**
 * Structural rules for the build data (format v1). It is pure: no database, no
 * request. Whether the palette pieces exist and are visible to the user is checked
 * by the ValidBuildData rule, because that needs the database.
 *
 * The TypeScript validator in the editor must behave the same. Both run the
 * shared vectors in tests/fixtures/build-validation.json.
 */
final class BuildDataValidator
{
    /** Piece ids are lowercase ULIDs (what HasUlids generates). */
    private const PIECE_ID = '/^[0-9a-z]{26}$/';

    /**
     * @param  array{width: int, height: int, depth: int, blocks: int, palette: int}  $limits
     */
    public function __construct(
        private readonly array $limits,
        private readonly int $version,
        private readonly int $maxErrors,
    ) {}

    public static function fromConfig(): self
    {
        /** @var array{width: int, height: int, depth: int, blocks: int, palette: int} $limits */
        $limits = config('builds.limits');

        return new self($limits, (int) config('builds.data_version'), (int) config('builds.max_errors'));
    }

    /**
     * @return list<BuildDataViolation> empty when the data is valid
     */
    public function validate(mixed $data, int $stepCount): array
    {
        if (! is_array($data)) {
            return [new BuildDataViolation(BuildDataError::DataInvalid)];
        }

        // Problems with the frame stop the check: the blocks cannot be read without it.
        if (($data['version'] ?? null) !== $this->version) {
            return [new BuildDataViolation(BuildDataError::VersionUnsupported)];
        }

        $size = $this->readSize($data['size'] ?? null);
        if ($size === null) {
            return [new BuildDataViolation(BuildDataError::SizeInvalid)];
        }

        $palette = $data['palette'] ?? null;
        if (! $this->isValidPalette($palette)) {
            return [new BuildDataViolation(BuildDataError::PaletteInvalid)];
        }

        $blocks = $data['blocks'] ?? null;
        if (! is_array($blocks) || ! array_is_list($blocks)) {
            return [new BuildDataViolation(BuildDataError::DataInvalid)];
        }

        if (count($blocks) > $this->limits['blocks']) {
            return [new BuildDataViolation(BuildDataError::TooManyBlocks)];
        }

        return $this->validateBlocks($blocks, $size, count($palette), $stepCount);
    }

    /**
     * @param  list<mixed>  $blocks
     * @param  array{w: int, h: int, d: int}  $size
     * @return list<BuildDataViolation>
     */
    private function validateBlocks(array $blocks, array $size, int $paletteCount, int $stepCount): array
    {
        ['w' => $w, 'h' => $h, 'd' => $d] = $size;

        $violations = [];
        $occupied = [];

        foreach ($blocks as $index => $block) {
            if (! $this->isSixIntegers($block)) {
                $violations[] = new BuildDataViolation(BuildDataError::BlockMalformed, $index);
            } else {
                [$x, $y, $z, $paletteIndex, $rotation, $step] = $block;

                $inside = $x >= 0 && $x < $w && $y >= 0 && $y < $h && $z >= 0 && $z < $d;

                if (! $inside) {
                    $violations[] = new BuildDataViolation(BuildDataError::BlockOutOfBounds, $index);
                }
                if ($paletteIndex < 0 || $paletteIndex >= $paletteCount) {
                    $violations[] = new BuildDataViolation(BuildDataError::PaletteIndexOutOfRange, $index);
                }
                if ($rotation < 0 || $rotation > 3) {
                    $violations[] = new BuildDataViolation(BuildDataError::RotationInvalid, $index);
                }
                if ($step < 0 || $step >= $stepCount) {
                    $violations[] = new BuildDataViolation(BuildDataError::StepOutOfRange, $index);
                }

                // Every block is one whole cell for now (cubes). When shapes arrive,the footprint of each piece replaces this single cell.
                if ($inside) {
                    $cell = ($y * $d + $z) * $w + $x;

                    if (isset($occupied[$cell])) {
                        $violations[] = new BuildDataViolation(BuildDataError::BlockOverlap, $index);
                    }
                    $occupied[$cell] = true;
                }
            }

            if (count($violations) >= $this->maxErrors) {
                break;
            }
        }

        return array_slice($violations, 0, $this->maxErrors);
    }

    /**
     * @return array{w: int, h: int, d: int}|null
     */
    private function readSize(mixed $size): ?array
    {
        if (! is_array($size)) {
            return null;
        }

        $w = $size['w'] ?? null;
        $h = $size['h'] ?? null;
        $d = $size['d'] ?? null;

        if (! is_int($w) || ! is_int($h) || ! is_int($d)) {
            return null;
        }

        if ($w < 1 || $w > $this->limits['width']
            || $h < 1 || $h > $this->limits['height']
            || $d < 1 || $d > $this->limits['depth']) {
            return null;
        }

        return ['w' => $w, 'h' => $h, 'd' => $d];
    }

    /**
     * @phpstan-assert-if-true list<string> $palette
     */
    private function isValidPalette(mixed $palette): bool
    {
        if (! is_array($palette) || ! array_is_list($palette) || count($palette) > $this->limits['palette']) {
            return false;
        }

        foreach ($palette as $id) {
            if (! is_string($id) || preg_match(self::PIECE_ID, $id) !== 1) {
                return false;
            }
        }

        return count(array_unique($palette)) === count($palette);
    }

    /**
     * @phpstan-assert-if-true array{int, int, int, int, int, int} $block
     */
    private function isSixIntegers(mixed $block): bool
    {
        if (! is_array($block) || ! array_is_list($block) || count($block) !== 6) {
            return false;
        }

        foreach ($block as $value) {
            if (! is_int($value)) {
                return false;
            }
        }

        return true;
    }
}
