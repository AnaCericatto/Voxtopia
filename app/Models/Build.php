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

// Only the title is user-editable. user_id, visibility, data and block_count are written by actions, never by mass assignment.
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
}
