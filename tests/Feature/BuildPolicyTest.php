<?php

use App\Models\Build;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

it('lets the owner view, update and delete', function (string $ability) {
    $owner = User::factory()->create();
    $build = Build::factory()->for($owner, 'owner')->create();

    expect(Gate::forUser($owner)->allows($ability, $build))->toBeTrue();
})->with(['view', 'update', 'delete']);

it('answers 404 to another user', function (string $ability) {
    $build = Build::factory()->create();
    $other = User::factory()->create();

    $response = Gate::forUser($other)->inspect($ability, $build);

    expect($response->allowed())->toBeFalse()
        ->and($response->status())->toBe(404);
})->with(['view', 'update', 'delete']);

it('answers 404 to a guest', function (string $ability) {
    $build = Build::factory()->create();

    $response = Gate::inspect($ability, $build);

    expect($response->allowed())->toBeFalse()
        ->and($response->status())->toBe(404);
})->with(['view', 'update', 'delete']);

it('lets any signed-in user list and create builds', function () {
    $user = User::factory()->create();

    expect(Gate::forUser($user)->allows('viewAny', Build::class))->toBeTrue()
        ->and(Gate::forUser($user)->allows('create', Build::class))->toBeTrue();
});
