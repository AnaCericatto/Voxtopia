<?php

namespace App\Enums;

/**
 * Stable codes for build data problems. The TypeScript validator reports the
 * same codes, and the shared vectors compare them (tests/fixtures/build-validation.json).
 */
enum BuildDataError: string
{
    case DataInvalid = 'data_invalid';
    case VersionUnsupported = 'version_unsupported';
    case SizeInvalid = 'size_invalid';
    case PaletteInvalid = 'palette_invalid';
    case PalettePieceUnavailable = 'palette_piece_unavailable';
    case TooManyBlocks = 'too_many_blocks';
    case BlockMalformed = 'block_malformed';
    case BlockOutOfBounds = 'block_out_of_bounds';
    case PaletteIndexOutOfRange = 'palette_index_out_of_range';
    case RotationInvalid = 'rotation_invalid';
    case StepOutOfRange = 'step_out_of_range';
    case BlockOverlap = 'block_overlap';
}
