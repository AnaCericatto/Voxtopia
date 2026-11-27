<?php

namespace Database\Factories;

use App\Enums\BuildVisibility;
use App\Models\Build;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Build>
 */
class BuildFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'title' => fake()->words(3, true),
            'visibility' => BuildVisibility::Private,
            'data' => [
                'version' => 1,
                'size' => ['w' => 32, 'h' => 16, 'd' => 32],
                'palette' => [],
                'blocks' => [],
            ],
            'block_count' => 0,
        ];
    }
}
