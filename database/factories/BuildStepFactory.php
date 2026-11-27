<?php

namespace Database\Factories;

use App\Models\Build;
use App\Models\BuildStep;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BuildStep>
 */
class BuildStepFactory extends Factory
{
    public function definition(): array
    {
        return [
            'build_id' => Build::factory(),
            'position' => 0,
            'title' => fake()->words(2, true),
            'note' => null,
        ];
    }
}
