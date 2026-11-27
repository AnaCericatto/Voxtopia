<?php

use App\Enums\BuildVisibility;
use App\Models\Build;
use App\Models\BuildStep;
use App\Models\Piece;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

function rawBuildRow(array $overrides = []): array
{
    return array_merge([
        'id' => (string) Str::ulid(),
        'user_id' => User::factory()->create()->id,
        'title' => 'Hut',
        'data' => '{}',
        'created_at' => now(),
        'updated_at' => now(),
    ], $overrides);
}

it('uses a ULID and keeps data as an array', function () {
    $build = Build::factory()->create(['data' => ['version' => 1, 'blocks' => []]]);

    // jsonb does not keep key order, so compare with toEqual
    expect($build->id)->toHaveLength(26)
        ->and($build->fresh()->data)->toEqual(['version' => 1, 'blocks' => []]);
});

it('is private by default in the database', function () {
    $row = rawBuildRow();
    DB::table('builds')->insert($row);

    expect(Build::find($row['id'])->visibility)->toBe(BuildVisibility::Private);
});

it('only lets the title be mass assigned', function () {
    expect((new Build)->getFillable())->toBe(['title']);
});

it('lists its steps ordered by position', function () {
    $build = Build::factory()->create();
    BuildStep::factory()->for($build)->create(['position' => 2]);
    BuildStep::factory()->for($build)->create(['position' => 0]);
    BuildStep::factory()->for($build)->create(['position' => 1]);

    expect($build->steps->pluck('position')->all())->toBe([0, 1, 2]);
});

it('counts the pieces it uses', function () {
    $build = Build::factory()->create();
    $piece = Piece::factory()->create();
    $build->pieces()->attach($piece, ['count' => 12]);

    expect($build->pieces()->first()->pivot->count)->toBe(12);
});

it('scopes builds to their owner', function () {
    $owner = User::factory()->create();
    Build::factory()->for($owner, 'owner')->count(2)->create();
    Build::factory()->create();

    expect(Build::ownedBy($owner)->count())->toBe(2)
        ->and($owner->builds()->count())->toBe(2);
});

it('soft deletes and keeps steps and pieces', function () {
    $build = Build::factory()->create();
    BuildStep::factory()->for($build)->create();

    $build->delete();

    expect(Build::find($build->id))->toBeNull()
        ->and(Build::withTrashed()->find($build->id))->not->toBeNull()
        ->and(BuildStep::count())->toBe(1);
});

it('removes steps and pieces on a force delete', function () {
    $build = Build::factory()->create();
    BuildStep::factory()->for($build)->create();
    $build->pieces()->attach(Piece::factory()->create(), ['count' => 2]);

    $build->forceDelete();

    expect(BuildStep::count())->toBe(0)
        ->and(DB::table('build_pieces')->count())->toBe(0);
});

it('removes the builds of a deleted user', function () {
    $user = User::factory()->create();
    Build::factory()->for($user, 'owner')->create();

    $user->delete();

    expect(Build::withTrashed()->count())->toBe(0);
});

it('rejects two steps at the same position', function () {
    $build = Build::factory()->create();
    BuildStep::factory()->for($build)->create(['position' => 0]);

    expect(fn () => BuildStep::factory()->for($build)->create(['position' => 0]))
        ->toThrow(QueryException::class);
});

it('does not let a piece that is in use be deleted', function () {
    $piece = Piece::factory()->create();
    Build::factory()->create()->pieces()->attach($piece, ['count' => 1]);

    expect(fn () => $piece->delete())->toThrow(QueryException::class);
});

it('rejects invalid values in the database', function (string $case) {
    $insert = match ($case) {
        'unknown visibility' => fn () => DB::table('builds')->insert(rawBuildRow(['visibility' => 'secret'])),
        'negative block count' => fn () => DB::table('builds')->insert(rawBuildRow(['block_count' => -1])),
        'negative step position' => fn () => DB::table('build_steps')->insert([
            'id' => (string) Str::ulid(),
            'build_id' => Build::factory()->create()->id,
            'position' => -1,
            'title' => 'Step',
        ]),
        'zero piece count' => fn () => DB::table('build_pieces')->insert([
            'build_id' => Build::factory()->create()->id,
            'piece_id' => Piece::factory()->create()->id,
            'count' => 0,
        ]),
    };

    expect($insert)->toThrow(QueryException::class);
})->with(['unknown visibility', 'negative block count', 'negative step position', 'zero piece count']);