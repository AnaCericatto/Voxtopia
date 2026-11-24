<?php

use App\Models\User;
use Illuminate\Support\Str;

it('uses a ULID as its primary key', function () {
    $user = User::factory()->create();

    expect(Str::isUlid($user->id))->toBeTrue();
});
