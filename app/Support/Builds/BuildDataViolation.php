<?php

namespace App\Support\Builds;

use App\Enums\BuildDataError;

/**
 * One problem found in the build data. `index` is the position of the block
 * (or of the palette entry) the problem belongs to, when there is one.
 */
final readonly class BuildDataViolation
{
    public function __construct(
        public BuildDataError $code,
        public ?int $index = null,
    ) {}
}
