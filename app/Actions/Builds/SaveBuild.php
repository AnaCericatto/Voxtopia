<?php

namespace App\Actions\Builds;

use App\Models\Build;
use Illuminate\Support\Facades\DB;

/**
 * @phpstan-import-type BuildData from Build
 */
final class SaveBuild
{
    /**
     * Saves the whole build in one transaction: title, data, block count, steps and the
     * derived materials list. The data must already be validated (ValidBuildData).
     *
     * @param  BuildData  $data
     * @param  array<array{title: string, note?: string|null}>  $steps  any array: Laravel's `array` rule does not require a list
     */
    public function handle(Build $build, string $title, array $data, array $steps): Build
    {
        // Only the known keys are stored, so nobody can keep extra JSON in the column.
        // palette and blocks are already lists: ValidBuildData rejects anything else.
        $clean = [
            'version' => $data['version'],
            'size' => ['w' => $data['size']['w'], 'h' => $data['size']['h'], 'd' => $data['size']['d']],
            'palette' => $data['palette'],
            'blocks' => $data['blocks'],
        ];

        $steps = array_values($steps);

        return DB::transaction(function () use ($build, $title, $clean, $steps): Build {
            $build->fill(['title' => $title]);
            $build->data = $clean;
            $build->block_count = count($clean['blocks']); // derived here, never taken from the client
            $build->updated_at = now(); // "last saved" changes even when only the steps changed
            $build->save();

            $build->steps()->delete();
            $build->steps()->createMany(array_map(
                fn (array $step, int $position): array => [
                    'position' => $position,
                    'title' => $step['title'],
                    'note' => $step['note'] ?? null,
                ],
                $steps,
                array_keys($steps),
            ));

            $build->pieces()->sync($this->materials($clean));

            return $build;
        });
    }

    /**
     * How many blocks use each piece: [pieceId => ['count' => n]]. Unused palette entries are left out.
     *
     * @param  BuildData  $clean
     * @return array<string, array{count: int}>
     */
    private function materials(array $clean): array
    {
        $materials = [];

        foreach (array_count_values(array_column($clean['blocks'], 3)) as $paletteIndex => $count) {
            $materials[$clean['palette'][$paletteIndex]] = ['count' => $count];
        }

        return $materials;
    }
}
