<?php

namespace App\Http\Resources;

use App\Models\Build;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A build as a row in "My builds". It has no `data`: the list query does not load that column.
 *
 * @mixin Build
 */
class BuildSummaryResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'visibility' => $this->visibility->value,
            'block_count' => $this->block_count,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
