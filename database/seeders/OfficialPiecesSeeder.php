<?php

namespace Database\Seeders;

use App\Enums\PieceKind;
use App\Enums\PieceVisibility;
use App\Models\Piece;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class OfficialPiecesSeeder extends Seeder
{
    public const SYSTEM_USERNAME = 'official';

    /**
     * The 32 official colors, name => hex.
     * The order is the order of creation, so it is the order of the palette.
     *
     * @var array<string, string>
     */
    public const COLORS = [
        // Neutrals
        'Snow' => '#F8FAFC',
        'Cloud' => '#D9DEE5',
        'Ash' => '#A3ABB5',
        'Slate' => '#6B7480',
        'Charcoal' => '#3A4049',
        'Ink' => '#1B1F26',
        // Earth
        'Sand' => '#E6D3A3',
        'Straw' => '#D9B56B',
        'Clay' => '#C27A4F',
        'Brick' => '#A4472F',
        'Soil' => '#6E4B33',
        'Bark' => '#4A3426',
        // Greens
        'Lime' => '#A6D854',
        'Leaf' => '#5DB04A',
        'Olive' => '#7C7A3A',
        'Pine' => '#2F7D4A',
        'Forest' => '#1F4F36',
        // Blues
        'Mint' => '#8FE3C4',
        'Teal' => '#2AA6A0',
        'Sky' => '#8FD0F5',
        'Ocean' => '#3F8FE0',
        'Deep Blue' => '#2A4FA8',
        'Navy' => '#1B2A5E',
        // Purples and pinks
        'Lavender' => '#B9A6E8',
        'Violet' => '#7A55D0',
        'Plum' => '#5A2D6E',
        'Rose' => '#F29BB6',
        'Magenta' => '#C93C84',
        // Warm
        'Gold' => '#F2B632',
        'Orange' => '#EE8130',
        'Coral' => '#F27B6B',
        'Red' => '#D8392F',
    ];

    public function run(): void
    {
        $system = User::firstOrCreate(
            ['username' => self::SYSTEM_USERNAME],
            [
                'name' => 'Official',
                'email' => 'official@example.invalid',
                'password' => Str::random(64),
            ],
        );

        Piece::unguarded(function () use ($system): void {
            foreach (self::COLORS as $name => $hex) {
                Piece::updateOrCreate(
                    ['name' => $name, 'is_official' => true],
                    [
                        'user_id' => $system->id,
                        'kind' => PieceKind::Cube,
                        'visibility' => PieceVisibility::Public,
                        'base_color' => $hex,
                    ],
                );
            }
        });
    }
}
