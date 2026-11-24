<?php

use App\Enums\BuildVisibility;
use App\Enums\PieceKind;
use App\Enums\PieceVisibility;

it('has the same cases in PHP and in TypeScript', function (string $enum, string $constant) {
    $source = file_get_contents(dirname(__DIR__, 2).'/resources/js/types/enums.ts');

    expect(preg_match("/export const {$constant} = \[(.*?)\] as const;/s", $source, $list))
        ->toBe(1, "{$constant} not found in enums.ts");

    preg_match_all("/'([a-z_]+)'/", $list[1], $values);

    $php = array_map(fn (BackedEnum $case) => $case->value, $enum::cases());

    expect($values[1])->toBe($php);
})->with([
    'PieceKind' => [PieceKind::class, 'pieceKinds'],
    'PieceVisibility' => [PieceVisibility::class, 'pieceVisibilities'],
    'BuildVisibility' => [BuildVisibility::class, 'buildVisibilities'],
]);
