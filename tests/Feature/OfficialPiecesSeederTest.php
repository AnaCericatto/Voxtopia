<?php

use App\Enums\PieceKind;
use App\Enums\PieceVisibility;
use App\Models\Piece;
use App\Models\User;
use Database\Seeders\OfficialPiecesSeeder;

it('creates the system account and the 32 official cubes', function () {
    $this->seed(OfficialPiecesSeeder::class);

    $system = User::where('username', OfficialPiecesSeeder::SYSTEM_USERNAME)->firstOrFail();

    expect(Piece::where('is_official', true)->count())->toBe(32);

    Piece::where('is_official', true)->each(function (Piece $piece) use ($system) {
        expect($piece->user_id)->toBe($system->id)
            ->and($piece->kind)->toBe(PieceKind::Cube)
            ->and($piece->visibility)->toBe(PieceVisibility::Public);
    });
});

it('is safe to run twice', function () {
    $this->seed(OfficialPiecesSeeder::class);
    $this->seed(OfficialPiecesSeeder::class);

    expect(Piece::where('is_official', true)->count())->toBe(32)
        ->and(User::where('username', OfficialPiecesSeeder::SYSTEM_USERNAME)->count())->toBe(1);
});

it('restores a color that was changed', function () {
    $this->seed(OfficialPiecesSeeder::class);

    Piece::where('name', 'Red')->update(['base_color' => '#000000']);
    $this->seed(OfficialPiecesSeeder::class);

    expect(Piece::where('name', 'Red')->value('base_color'))->toBe('#D8392F');
});

it('has 32 unique names and 32 unique valid colors', function () {
    $colors = OfficialPiecesSeeder::COLORS;

    expect($colors)->toHaveCount(32)
        ->and(array_unique(array_values($colors)))->toHaveCount(32);

    foreach ($colors as $hex) {
        expect($hex)->toMatch('/^#[0-9A-F]{6}$/');
    }
});
