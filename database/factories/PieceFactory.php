<?php

namespace Database\Factories;

use App\Enums\PieceKind;
use App\Enums\PieceVisibility;
use App\Models\Piece;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Piece>
 */
class PieceFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'kind' => PieceKind::Cube,
            'name' => fake()->unique()->words(2, true),
            'visibility' => PieceVisibility::Private,
            'is_official' => false,
            'size_w' => 1,
            'size_h' => 1,
            'size_d' => 1,
            'base_color' => fake()->hexColor(),
        ];
    }

    public function official(): static
    {
        return $this->state(fn () => [
            'is_official' => true,
            'visibility' => PieceVisibility::Public,
        ]);
    }

    public function public(): static
    {
        return $this->state(fn () => ['visibility' => PieceVisibility::Public]);
    }
}
