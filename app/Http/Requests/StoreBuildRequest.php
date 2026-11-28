<?php

namespace App\Http\Requests;

use App\Models\Build;
use Illuminate\Foundation\Http\FormRequest;

class StoreBuildRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Build::class) ?? false;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        $limits = config('builds.limits');

        return [
            'title' => ['required', 'string', 'max:'.$limits['title']],
            'size' => ['required', 'array'],
            'size.w' => ['required', 'integer', 'between:1,'.$limits['width']],
            'size.h' => ['required', 'integer', 'between:1,'.$limits['height']],
            'size.d' => ['required', 'integer', 'between:1,'.$limits['depth']],
        ];
    }
}
