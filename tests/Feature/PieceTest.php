<?php

use App\Models\Piece;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

it('uses a ULID as its primary key', function () {
    expect(Str::isUlid(Piece::factory()->create()->id))->toBeTrue();
});

it('stores the color in capital letters', function () {
    $piece = Piece::factory()->create(['base_color' => '#aabbcc']);

    expect($piece->base_color)->toBe('#AABBCC');
});

it('shows a guest only official and public pieces', function () {
    $official = Piece::factory()->official()->create();
    $public = Piece::factory()->public()->create();
    Piece::factory()->create();

    $ids = Piece::visibleTo(null)->pluck('id');

    expect($ids)->toHaveCount(2)->toContain($official->id, $public->id);
});

it('shows the owner their own private pieces', function () {
    $owner = User::factory()->create();
    $mine = Piece::factory()->for($owner, 'owner')->create();
    Piece::factory()->create();

    expect(Piece::visibleTo($owner)->pluck('id')->all())->toBe([$mine->id]);
});

it('hides a private piece from other users', function () {
    $piece = Piece::factory()->create();
    $other = User::factory()->create();

    expect(Piece::visibleTo($other)->whereKey($piece->id)->exists())->toBeFalse();
});

it('does not allow two official pieces with the same name', function () {
    Piece::factory()->official()->create(['name' => 'Stone']);

    expect(fn () => Piece::factory()->official()->create(['name' => 'Stone']))
        ->toThrow(QueryException::class);
});

it('allows the same name for pieces that are not official', function () {
    Piece::factory()->official()->create(['name' => 'Stone']);
    Piece::factory()->create(['name' => 'Stone']);

    expect(Piece::where('name', 'Stone')->count())->toBe(2);
});

it('rejects invalid values at the database level', function (array $override) {
    $user = User::factory()->create();

    $row = array_merge([
        'id' => (string) Str::ulid(),
        'user_id' => $user->id,
        'kind' => 'cube',
        'name' => 'Test',
        'visibility' => 'private',
        'is_official' => false,
        'size_w' => 1,
        'size_h' => 1,
        'size_d' => 1,
        'base_color' => '#FFFFFF',
        'created_at' => now(),
        'updated_at' => now(),
    ], $override);

    expect(fn () => DB::table('pieces')->insert($row))->toThrow(QueryException::class);
})->with([
    'unknown kind' => [['kind' => 'banana']],
    'unlisted piece' => [['visibility' => 'unlisted']],
    'color without #' => [['base_color' => 'FF0000']],
    'zero width' => [['size_w' => 0]],
]);
