<?php

namespace App\Http\Requests;

use App\Models\User;
use App\Rules\ValidBuildData;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class UpdateBuildRequest extends FormRequest
{
    /**
     * Runs before validation, so another user's build answers 404 and nothing
     * about the payload (or the pieces it names) is checked or revealed.
     */
    public function authorize(): bool
    {
        return Gate::authorize('update', $this->route('build'))->allowed();
    }

    /**
     * @return array<string, array<int, string|ValidationRule>>
     */
    public function rules(): array
    {
        $limits = config('builds.limits');

        /** @var User $user */
        $user = $this->user();

        $steps = $this->input('steps');
        $stepCount = is_array($steps) ? count($steps) : 0;

        return [
            'title' => ['required', 'string', 'max:'.$limits['title']],
            'steps' => ['required', 'array', 'min:1', 'max:'.$limits['steps']],
            'steps.*.title' => ['required', 'string', 'max:'.$limits['step_title']],
            'steps.*.note' => ['nullable', 'string', 'max:'.$limits['step_note']],
            'data' => ['required', 'array', new ValidBuildData($user, $stepCount)],
        ];
    }
}
