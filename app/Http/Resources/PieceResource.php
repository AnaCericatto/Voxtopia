<?php

namespace App\Http\Resources;

use App\Models\Piece;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Piece */
class PieceResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'kind' => $this->kind->value,
            'base_color' => $this->base_color,
            'is_official' => $this->is_official,
            // Only present when the piece comes from a build's materials list.
            'count' => $this->whenPivotLoaded('build_pieces', fn () => data_get($this->resource, 'pivot.count')),
        ];
    }
}
