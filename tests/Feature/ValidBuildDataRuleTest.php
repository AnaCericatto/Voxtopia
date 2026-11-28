<?php

use App\Models\Piece;
use App\Models\User;
use App\Rules\ValidBuildData;
use Illuminate\Support\Facades\Validator;

/**
 * @param  list<string>  $palette
 * @param  list<mixed>  $blocks
 */
function dataFor(array $palette, array $blocks = []): array
{
    return [
        'version' => 1,
        'size' => ['w' => 8, 'h' => 8, 'd' => 8],
        'palette' => $palette,
        'blocks' => $blocks,
    ];
}

function runRule(User $user, array $data, int $steps = 1): Illuminate\Validation\Validator
{
    return Validator::make(['data' => $data], ['data' => ['required', 'array', new ValidBuildData($user, $steps)]]);
}

it('accepts official, public and own pieces', function () {
    $user = User::factory()->create();
    $official = Piece::factory()->official()->create();
    $public = Piece::factory()->public()->create();
    $own = Piece::factory()->for($user, 'owner')->create();

    $validator = runRule($user, dataFor([$official->id, $public->id, $own->id], [[0, 0, 0, 2, 0, 0]]));

    expect($validator->passes())->toBeTrue();
});

it('rejects a private piece of another user', function () {
    $user = User::factory()->create();
    $official = Piece::factory()->official()->create();
    $foreign = Piece::factory()->create(); // private, owned by someone else

    $validator = runRule($user, dataFor([$official->id, $foreign->id]));

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->first('data'))->toContain('Palette entry 2');
});

it('rejects a piece that does not exist', function () {
    $user = User::factory()->create();

    $validator = runRule($user, dataFor(['01zzzzzzzzzzzzzzzzzzzzzzzz']));

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->first('data'))->toContain('Palette entry 1');
});

it('reports one readable message for each problem', function () {
    $user = User::factory()->create();
    $official = Piece::factory()->official()->create();

    $validator = runRule($user, dataFor([$official->id], [[99, 0, 0, 0, 0, 0], [0, 0, 0, 0, 9, 0]]));

    expect($validator->errors()->get('data'))->toBe([
        'Block 1 is outside the build area.',
        'Block 2 has an invalid rotation.',
    ]);
});

it('explains an unsupported version in words', function () {
    $user = User::factory()->create();

    $validator = runRule($user, ['version' => 3]);

    expect($validator->errors()->first('data'))->toContain('format version');
});
