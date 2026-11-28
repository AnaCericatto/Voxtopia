<?php

use App\Actions\Builds\SaveBuild;
use App\Models\Build;
use App\Models\Piece;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

/**
 * A valid save payload that uses the given piece (3 blocks in 2 steps).
 *
 * @return array<string, mixed>
 */
function savePayload(Piece $piece, array $overrides = []): array
{
    return array_merge([
        'title' => 'My house',
        'steps' => [
            ['title' => 'Walls', 'note' => 'Start with the walls'],
            ['title' => 'Roof', 'note' => null],
        ],
        'data' => [
            'version' => 1,
            'size' => ['w' => 16, 'h' => 8, 'd' => 16],
            'palette' => [$piece->id],
            'blocks' => [[0, 0, 0, 0, 0, 0], [1, 0, 0, 0, 0, 0], [2, 0, 0, 0, 1, 1]],
        ],
    ], $overrides);
}

/** A build of this user that was saved through the real action. */
function savedBuild(User $user, ?Piece $piece = null): Build
{
    $piece ??= Piece::factory()->official()->create();
    $payload = savePayload($piece);

    $build = Build::factory()->for($user, 'owner')->create();
    app(SaveBuild::class)->handle($build, $payload['title'], $payload['data'], $payload['steps']);

    return $build->fresh();
}

// --- guests ---

it('sends guests to the login page', function () {
    $this->get(route('builds.index'))->assertRedirect(route('login'));
});

// --- index ---

it('lists only my builds, newest first, without their data', function () {
    $user = User::factory()->create();
    Build::factory()->for($user, 'owner')->create(['title' => 'Older', 'created_at' => now()->subDay()]);
    Build::factory()->for($user, 'owner')->create(['title' => 'Newer']);
    Build::factory()->create(['title' => 'Not mine']);

    $this->actingAs($user)->get(route('builds.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('builds/index', false)
            ->has('builds', 2)
            ->where('builds.0.title', 'Newer')
            ->where('builds.1.title', 'Older')
            ->missing('builds.0.data')
            ->where('limits.blocks', 20000));
});

// --- store ---

it('creates an empty private build with one step and opens the editor', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('builds.store'), ['title' => 'Hut', 'size' => ['w' => 16, 'h' => 8, 'd' => 12]])
        ->assertRedirect();

    $build = $user->builds()->firstOrFail();

    expect($build->title)->toBe('Hut')
        ->and($build->visibility->value)->toBe('private')
        ->and($build->block_count)->toBe(0)
        ->and($build->data)->toEqual([
            'version' => 1,
            'size' => ['w' => 16, 'h' => 8, 'd' => 12],
            'palette' => [],
            'blocks' => [],
        ])
        ->and($build->steps)->toHaveCount(1)
        ->and($build->steps->first()->title)->toBe('Step 1');

    $this->actingAs($user)->get(route('builds.edit', $build))->assertOk();
});

it('refuses a new build with a bad title or size', function (array $payload) {
    $user = User::factory()->create();

    $this->actingAs($user)->post(route('builds.store'), $payload)->assertSessionHasErrors();

    expect(Build::count())->toBe(0);
})->with([
    'no title' => [['size' => ['w' => 16, 'h' => 8, 'd' => 16]]],
    'no size' => [['title' => 'Hut']],
    'too wide' => [['title' => 'Hut', 'size' => ['w' => 65, 'h' => 8, 'd' => 16]]],
    'too high' => [['title' => 'Hut', 'size' => ['w' => 16, 'h' => 33, 'd' => 16]]],
    'zero depth' => [['title' => 'Hut', 'size' => ['w' => 16, 'h' => 8, 'd' => 0]]],
]);

it('stops at the limit of builds per user', function () {
    config(['builds.limits.builds_per_user' => 2]);
    $user = User::factory()->create();
    Build::factory()->for($user, 'owner')->count(2)->create();

    $this->actingAs($user)
        ->post(route('builds.store'), ['title' => 'One too many', 'size' => ['w' => 16, 'h' => 8, 'd' => 16]])
        ->assertSessionHasErrors('limit');

    expect($user->builds()->count())->toBe(2);
});

it('does not count deleted builds towards the limit', function () {
    config(['builds.limits.builds_per_user' => 2]);
    $user = User::factory()->create();
    Build::factory()->for($user, 'owner')->create();
    Build::factory()->for($user, 'owner')->create()->delete();

    $this->actingAs($user)
        ->post(route('builds.store'), ['title' => 'Fits', 'size' => ['w' => 16, 'h' => 8, 'd' => 16]])
        ->assertSessionHasNoErrors();

    expect($user->builds()->count())->toBe(2);
});

// --- show and edit ---

it('shows my build with its palette and materials', function () {
    $user = User::factory()->create();
    $build = savedBuild($user);

    $this->actingAs($user)->get(route('builds.show', $build))
        ->assertInertia(fn (Assert $page) => $page
            ->component('builds/show', false)
            ->where('build.title', 'My house')
            ->where('build.block_count', 3)
            ->has('build.steps', 2)
            ->where('build.steps.0.title', 'Walls')
            ->has('palette', 1)
            ->has('materials', 1)
            ->where('materials.0.count', 3));
});

it('gives the editor the official pieces and the limits', function () {
    $user = User::factory()->create();
    $build = savedBuild($user);
    Piece::factory()->official()->count(2)->create();

    $this->actingAs($user)->get(route('builds.edit', $build))
        ->assertInertia(fn (Assert $page) => $page
            ->component('builds/edit', false)
            ->has('officialPieces', 3)
            ->where('limits.width', 64)
            ->where('build.data.size.w', 16));
});

it('answers 404 to a build that was deleted', function () {
    $user = User::factory()->create();
    $build = savedBuild($user);
    $build->delete();

    $this->actingAs($user)->get(route('builds.show', $build))->assertNotFound();
});

// --- other users ---

it('answers 404 to other users', function (string $action) {
    $build = savedBuild(User::factory()->create());
    $other = User::factory()->create();
    $payload = savePayload(Piece::factory()->official()->create());

    $response = match ($action) {
        'show' => $this->actingAs($other)->get(route('builds.show', $build)),
        'edit' => $this->actingAs($other)->get(route('builds.edit', $build)),
        'update' => $this->actingAs($other)->put(route('builds.update', $build), $payload),
        'destroy' => $this->actingAs($other)->delete(route('builds.destroy', $build)),
    };

    $response->assertNotFound();
    expect($build->fresh()->title)->toBe('My house')
        ->and(Build::find($build->id))->not->toBeNull();
})->with(['show', 'edit', 'update', 'destroy']);

// --- update ---

it('saves the title, data, block count, steps and materials', function () {
    $user = User::factory()->create();
    $piece = Piece::factory()->official()->create();
    $build = Build::factory()->for($user, 'owner')->create();

    $this->actingAs($user)->put(route('builds.update', $build), savePayload($piece))
        ->assertSessionHasNoErrors()
        ->assertRedirect();

    $build->refresh();

    expect($build->title)->toBe('My house')
        ->and($build->block_count)->toBe(3)
        ->and($build->data['palette'])->toBe([$piece->id])
        ->and($build->data['blocks'])->toHaveCount(3)
        ->and($build->steps->pluck('title')->all())->toBe(['Walls', 'Roof'])
        ->and($build->steps->pluck('position')->all())->toBe([0, 1])
        ->and($build->pieces)->toHaveCount(1)
        ->and($build->pieces->first()->pivot->count)->toBe(3);
});

it('replaces the steps and the materials when saved again', function () {
    $user = User::factory()->create();
    $piece = Piece::factory()->official()->create();
    $build = savedBuild($user, $piece);

    $empty = savePayload($piece, [
        'steps' => [['title' => 'Only step', 'note' => null]],
        'data' => ['version' => 1, 'size' => ['w' => 16, 'h' => 8, 'd' => 16], 'palette' => [$piece->id], 'blocks' => []],
    ]);

    $this->actingAs($user)->put(route('builds.update', $build), $empty)->assertSessionHasNoErrors();

    $build->refresh();

    expect($build->block_count)->toBe(0)
        ->and($build->steps)->toHaveCount(1)
        ->and($build->pieces)->toHaveCount(0);
});

it('keeps an unused palette entry out of the materials', function () {
    $user = User::factory()->create();
    [$used, $unused] = Piece::factory()->official()->count(2)->create()->all();
    $build = Build::factory()->for($user, 'owner')->create();

    $payload = savePayload($used);
    $payload['data']['palette'] = [$used->id, $unused->id];

    $this->actingAs($user)->put(route('builds.update', $build), $payload)->assertSessionHasNoErrors();

    $build->refresh();

    expect($build->data['palette'])->toBe([$used->id, $unused->id])
        ->and($build->pieces->pluck('id')->all())->toBe([$used->id]);
});

it('ignores a block count sent by the client', function () {
    $user = User::factory()->create();
    $piece = Piece::factory()->official()->create();
    $build = Build::factory()->for($user, 'owner')->create();

    $this->actingAs($user)->put(route('builds.update', $build), savePayload($piece) + ['block_count' => 5])
        ->assertSessionHasNoErrors();

    expect($build->fresh()->block_count)->toBe(3);
});

it('stores only the known keys of the data', function () {
    $user = User::factory()->create();
    $piece = Piece::factory()->official()->create();
    $build = Build::factory()->for($user, 'owner')->create();

    $payload = savePayload($piece);
    $payload['data']['extra'] = str_repeat('x', 1000);
    $payload['data']['size']['depth'] = 99;

    $this->actingAs($user)->put(route('builds.update', $build), $payload)->assertSessionHasNoErrors();

    $data = $build->fresh()->data;

    expect(array_keys($data))->toEqualCanonicalizing(['version', 'size', 'palette', 'blocks'])
        ->and(array_keys($data['size']))->toEqualCanonicalizing(['w', 'h', 'd']);
});

it('refuses invalid data and saves nothing', function (string $case) {
    $user = User::factory()->create();
    $piece = Piece::factory()->official()->create();
    $build = savedBuild($user, $piece);
    $foreign = Piece::factory()->create(); // private piece of another user

    $payload = savePayload($piece, ['title' => 'Changed']);

    match ($case) {
        'block outside the grid' => $payload['data']['blocks'][] = [99, 0, 0, 0, 0, 0],
        'block in a step that does not exist' => $payload['data']['blocks'][] = [3, 0, 0, 0, 0, 7],
        'two blocks in one cell' => $payload['data']['blocks'][] = [0, 0, 0, 0, 0, 0],
        'piece of another user' => $payload['data']['palette'] = [$foreign->id],
        'unknown piece' => $payload['data']['palette'] = ['01zzzzzzzzzzzzzzzzzzzzzzzz'],
    };

    $this->actingAs($user)->put(route('builds.update', $build), $payload)->assertSessionHasErrors('data');

    $build->refresh();

    expect($build->title)->toBe('My house')
        ->and($build->block_count)->toBe(3)
        ->and($build->steps)->toHaveCount(2);
})->with([
    'block outside the grid',
    'block in a step that does not exist',
    'two blocks in one cell',
    'piece of another user',
    'unknown piece',
]);

it('refuses bad titles and steps', function (string $field, mixed $value) {
    $user = User::factory()->create();
    $piece = Piece::factory()->official()->create();
    $build = savedBuild($user, $piece);

    $payload = savePayload($piece);
    data_set($payload, $field, $value);

    $this->actingAs($user)->put(route('builds.update', $build), $payload)->assertSessionHasErrors();
})->with([
    'no title' => ['title', ''],
    'title too long' => ['title', str_repeat('a', 101)],
    'no steps' => ['steps', []],
    'too many steps' => ['steps', array_fill(0, 101, ['title' => 'Step', 'note' => null])],
    'step without a title' => ['steps.0.title', ''],
    'step title too long' => ['steps.0.title', str_repeat('a', 101)],
    'step note too long' => ['steps.0.note', str_repeat('a', 2001)],
]);

// --- destroy ---

it('deletes my build softly and goes back to the list', function () {
    $user = User::factory()->create();
    $build = savedBuild($user);

    $this->actingAs($user)->delete(route('builds.destroy', $build))
        ->assertRedirect(route('builds.index'));

    $this->assertSoftDeleted($build);
});
