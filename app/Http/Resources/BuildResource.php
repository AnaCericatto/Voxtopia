<?php

namespace App\Http\Resources;

use App\Models\Build;
use App\Models\BuildStep;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A whole build, for the editor and the viewer. Load `steps` before using it.
 *
 * @mixin Build
 */
class BuildResource extends JsonResource
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
            'data' => $this->data,
            'steps' => $this->whenLoaded('steps', fn () => $this->steps->map(fn (BuildStep $step): array => [
                'title' => $step->title,
                'note' => $step->note,
            ])->all()),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
