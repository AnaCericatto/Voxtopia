<?php

namespace App\Models;

use App\Enums\BuildVisibility;
use Database\Factories\BuildFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;

// Only the title is user-editable. user_id, visibility, data and block_count are written by actions, never by mass assignment.
/**
 * @phpstan-type BuildData array{
 *     version: int,
 *     size: array{w: int, h: int, d: int},
 *     palette: list<string>,
 *     blocks: list<array{int, int, int, int, int, int}>
 * }
 *
 * @property BuildVisibility $visibility
 * @property BuildData $data
 */
#[Fillable(['title'])]
class Build extends Model
{
    /** @use HasFactory<BuildFactory> */
    use HasFactory, HasUlids, SoftDeletes;

    protected function casts(): array
    {
        return [
            'visibility' => BuildVisibility::class,
            'data' => 'array',
            'block_count' => 'integer',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /** @return HasMany<BuildStep, $this> */
    public function steps(): HasMany
    {
        return $this->hasMany(BuildStep::class)->orderBy('position');
    }

    /** @return BelongsToMany<Piece, $this> */
    public function pieces(): BelongsToMany
    {
        return $this->belongsToMany(Piece::class, 'build_pieces')->withPivot('count');
    }

    /** @param Builder<Build> $query */
    public function scopeOwnedBy(Builder $query, User $user): void
    {
        $query->where('user_id', $user->id);
    }

    /**
     * The palette pieces in palette order, so a block's palette index points at the right one.
     *
     * @return Collection<int, Piece|null>
     */
    public function palettePieces(): Collection
    {
        $ids = $this->data['palette'];

        $pieces = Piece::query()->whereIn('id', $ids)->get()->keyBy('id');

        // A missing piece would be null. M4 (rule R2) decides what to show then.
        return collect($ids)->map(fn (string $id) => $pieces->get($id));
    }
}
