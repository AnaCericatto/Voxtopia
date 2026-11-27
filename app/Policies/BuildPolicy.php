<?php

namespace App\Policies;

use App\Models\Build;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class BuildPolicy
{
    public function viewAny(User $user): bool
    {
        return true; // the list is always scoped to the owner's own builds
    }

    public function view(?User $user, Build $build): Response
    {
        return $this->ownerOnly($user, $build);
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(?User $user, Build $build): Response
    {
        return $this->ownerOnly($user, $build);
    }

    public function delete(?User $user, Build $build): Response
    {
        return $this->ownerOnly($user, $build);
    }

    // Private builds answers 404
    private function ownerOnly(?User $user, Build $build): Response
    {
        return $user !== null && $build->user_id === $user->id
            ? Response::allow()
            : Response::denyAsNotFound();
    }
}
