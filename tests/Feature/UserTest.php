<?php

use App\Models\User;
use Illuminate\Support\Str;

it('uses a ULID as its primary key', function () {
    $user = User::factory()->create();

    expect(Str::isUlid($user->id))->toBeTrue();
});

it('does not let anyone register the system username', function () {
    expect(User::RESERVED_USERNAMES)->toContain('official', 'system');
});
