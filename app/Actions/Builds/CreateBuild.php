<?php

namespace App\Actions\Builds;

use App\Enums\BuildVisibility;
use App\Models\Build;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class CreateBuild
{
    /**
     * Creates an empty private build with one step ("New build" dialog, D8).
     *
     * @throws ValidationException when the user already has the maximum number of builds
     */
    public function handle(User $user, string $title, int $width, int $height, int $depth): Build
    {
        return DB::transaction(function () use ($user, $title, $width, $height, $depth): Build {
            // Lock the user's row so two requests at the same time cannot both pass the limit check.
            User::query()->whereKey($user->id)->lockForUpdate()->first();

            $limit = (int) config('builds.limits.builds_per_user');

            if ($user->builds()->count() >= $limit) {
                throw ValidationException::withMessages([
                    'limit' => __('builds.limit_reached', ['limit' => $limit]),
                ]);
            }

            $build = $user->builds()->make(['title' => $title]);
            $build->visibility = BuildVisibility::Private;
            $build->block_count = 0;
            $build->data = [
                'version' => (int) config('builds.data_version'),
                'size' => ['w' => $width, 'h' => $height, 'd' => $depth],
                'palette' => [],
                'blocks' => [],
            ];
            $build->save();

            $build->steps()->create([
                'position' => 0,
                'title' => __('builds.default_step_title', ['number' => 1]),
                'note' => null,
            ]);

            return $build;
        });
    }
}
