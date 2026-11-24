<?php

namespace App\Models;

use App\Enums\PieceKind;
use App\Enums\PieceVisibility;
use Database\Factories\PieceFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string $user_id
 * @property PieceKind $kind
 * @property string $name
 * @property PieceVisibility $visibility
 * @property bool $is_official
 * @property int $size_w
 * @property int $size_h
 * @property int $size_d
 * @property string $base_color
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['name', 'visibility', 'base_color'])]
class Piece extends Model
{
    /** @use HasFactory<PieceFactory> */
    use HasFactory, HasUlids;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'kind' => PieceKind::class,
            'visibility' => PieceVisibility::class,
            'is_official' => 'boolean',
        ];
    }

    /**
     * Colors are always stored as #RRGGBB in capital letters.
     *
     * @return Attribute<string, string>
     */
    protected function baseColor(): Attribute
    {
        return Attribute::set(fn (string $value) => strtoupper($value));
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Pieces the viewer may see: official, public, or their own.
     *
     * @param  Builder<Piece>  $query
     */
    public function scopeVisibleTo(Builder $query, ?User $user): void
    {
        $query->where(function (Builder $query) use ($user) {
            $query->where('is_official', true)
                ->orWhere('visibility', PieceVisibility::Public->value);

            if ($user !== null) {
                $query->orWhere('user_id', $user->id);
            }
        });
    }
}
