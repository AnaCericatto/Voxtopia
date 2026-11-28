<?php

use App\Enums\BuildDataError;
use App\Support\Builds\BuildDataValidator;

const PIECE_A = '01aaaaaaaaaaaaaaaaaaaaaaaa';

/**
 * Builds valid data for a grid with the given blocks.
 *
 * @param  list<mixed>  $blocks
 * @return array<string, mixed>
 */
function buildData(array $blocks = [], int $w = 64, int $h = 32, int $d = 64): array
{
    return [
        'version' => 1,
        'size' => ['w' => $w, 'h' => $h, 'd' => $d],
        'palette' => [PIECE_A],
        'blocks' => $blocks,
    ];
}

// The same vectors run in Vitest (M1.7), so PHP and TypeScript cannot drift apart.
dataset('build-validation vectors', function () {
    $file = json_decode(
        file_get_contents(__DIR__.'/../fixtures/build-validation.json'),
        true,
        flags: JSON_THROW_ON_ERROR,
    );

    $cases = [];
    foreach ($file['cases'] as $case) {
        $cases[$case['name']] = [$case];
    }

    return $cases;
});

it('follows the shared vectors', function (array $case) {
    $violations = BuildDataValidator::fromConfig()->validate($case['data'], $case['steps']);

    $actual = array_map(
        fn ($violation) => array_filter(
            ['code' => $violation->code->value, 'index' => $violation->index],
            fn ($value) => $value !== null,
        ),
        $violations,
    );

    expect($actual)->toEqual($case['expect'] === 'valid' ? [] : $case['expect']);
})->with('build-validation vectors');

it('accepts the maximum number of blocks', function () {
    $blocks = [];
    for ($i = 0; $i < config('builds.limits.blocks'); $i++) {
        $blocks[] = [$i % 64, intdiv($i, 4096), intdiv($i, 64) % 64, 0, 0, 0];
    }

    expect(BuildDataValidator::fromConfig()->validate(buildData($blocks), 1))->toBe([]);
});

it('rejects one block more than the limit', function () {
    $blocks = array_fill(0, config('builds.limits.blocks') + 1, [0, 0, 0, 0, 0, 0]);

    $violations = BuildDataValidator::fromConfig()->validate(buildData($blocks), 1);

    expect($violations)->toHaveCount(1)
        ->and($violations[0]->code)->toBe(BuildDataError::TooManyBlocks);
});

it('accepts a palette of 32 pieces and rejects 33', function () {
    $ids = fn (int $count) => array_map(
        fn (int $i) => '01'.str_pad(dechex($i), 24, '0', STR_PAD_LEFT),
        range(0, $count - 1),
    );

    $valid = ['version' => 1, 'size' => ['w' => 4, 'h' => 3, 'd' => 4], 'palette' => $ids(32), 'blocks' => []];
    $tooMany = [...$valid, 'palette' => $ids(33)];

    $validator = BuildDataValidator::fromConfig();

    expect($validator->validate($valid, 1))->toBe([])
        ->and($validator->validate($tooMany, 1)[0]->code)->toBe(BuildDataError::PaletteInvalid);
});

it('reports at most 10 problems', function () {
    $blocks = array_fill(0, 12, [99, 0, 0, 0, 0, 0]); // all outside the grid

    $violations = BuildDataValidator::fromConfig()->validate(buildData($blocks), 1);

    expect($violations)->toHaveCount(10)
        ->and($violations[9]->index)->toBe(9);
});

it('reads its limits from the config', function () {
    config(['builds.limits.width' => 8]);

    $violations = BuildDataValidator::fromConfig()->validate(buildData([], w: 9), 1);

    expect($violations[0]->code)->toBe(BuildDataError::SizeInvalid);
});
