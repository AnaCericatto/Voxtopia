<?php

namespace App\Http\Controllers;

use App\Actions\Builds\CreateBuild;
use App\Actions\Builds\SaveBuild;
use App\Http\Requests\StoreBuildRequest;
use App\Http\Requests\UpdateBuildRequest;
use App\Http\Resources\BuildResource;
use App\Http\Resources\BuildSummaryResource;
use App\Http\Resources\PieceResource;
use App\Models\Build;
use App\Models\Piece;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

/**
 * @phpstan-import-type BuildData from Build
 */
class BuildController extends Controller
{
    /** "My builds". At most 50 per user, so no pagination. */
    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', Build::class);

        // Not the `data` column: it can weigh half a megabyte per build.
        $builds = $request->user()->builds()
            ->select(['id', 'user_id', 'title', 'visibility', 'block_count', 'created_at', 'updated_at'])
            ->latest()
            ->orderByDesc('id')
            ->get();

        return Inertia::render('builds/index', [
            'builds' => BuildSummaryResource::collection($builds),
            'limits' => config('builds.limits'),
        ]);
    }

    /** The "New build" dialog posts here, then the editor opens (D8). */
    public function store(StoreBuildRequest $request, CreateBuild $createBuild): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        $build = $createBuild->handle(
            $user,
            $request->string('title')->toString(),
            $request->integer('size.w'),
            $request->integer('size.h'),
            $request->integer('size.d'),
        );

        return to_route('builds.edit', $build);
    }

    /** The viewer: read-only, with the materials list. */
    public function show(Build $build): Response
    {
        Gate::authorize('view', $build);

        $build->load(['steps', 'pieces']);

        return Inertia::render('builds/show', [
            'build' => new BuildResource($build),
            'palette' => PieceResource::collection($build->palettePieces()),
            'materials' => PieceResource::collection($build->pieces),
        ]);
    }

    public function edit(Build $build): Response
    {
        Gate::authorize('view', $build);

        $build->load('steps');

        return Inertia::render('builds/edit', [
            'build' => new BuildResource($build),
            'palette' => PieceResource::collection($build->palettePieces()),
            'officialPieces' => PieceResource::collection(
                Piece::query()->where('is_official', true)->orderBy('id')->get()
            ),
            'limits' => config('builds.limits'),
        ]);
    }

    /** Save: replaces the title, the data and the steps in one go. */
    public function update(UpdateBuildRequest $request, Build $build, SaveBuild $saveBuild): RedirectResponse
    {
        /** @var BuildData $data */
        $data = $request->validated('data');

        /** @var array<array{title: string, note?: string|null}> $steps */
        $steps = $request->validated('steps');

        $saveBuild->handle($build, $request->string('title')->toString(), $data, $steps);

        return back();
    }

    public function destroy(Build $build): RedirectResponse
    {
        Gate::authorize('delete', $build);

        $build->delete();

        return to_route('builds.index');
    }
}
