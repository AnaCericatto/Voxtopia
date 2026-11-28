<?php

namespace App\Rules;

use App\Enums\BuildDataError;
use App\Models\Piece;
use App\Models\User;
use App\Support\Builds\BuildDataValidator;
use App\Support\Builds\BuildDataViolation;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Validates the `data` of a build: the structural rules (BuildDataValidator)
 * and that every palette piece exists and is visible to the user.
 *
 * Use it together with 'required' (a rule does not run on null):
 *     'data' => ['required', 'array', new ValidBuildData($this->user(), count($this->input('steps', [])))]
 */
class ValidBuildData implements ValidationRule
{
    public function __construct(
        private readonly User $user,
        private readonly int $stepCount,
    ) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $violations = BuildDataValidator::fromConfig()->validate($value, $this->stepCount);

        // The database is only asked when the structure is fine.
        if ($violations === [] && is_array($value)) {
            /** @var list<string> $palette */
            $palette = $value['palette'];
            $violations = $this->unavailablePieces($palette);
        }

        foreach ($violations as $violation) {
            $replace = ['limit' => config('builds.limits.palette')];

            if ($violation->index !== null) {
                $replace['number'] = $violation->index + 1;
            }

            $fail('builds.errors.'.$violation->code->value)->translate($replace);
        }
    }

    /**
     * @param  list<string>  $palette
     * @return list<BuildDataViolation>
     */
    private function unavailablePieces(array $palette): array
    {
        $visible = Piece::query()
            ->visibleTo($this->user)
            ->whereIn('id', $palette)
            ->pluck('id')
            ->all();

        $violations = [];

        foreach ($palette as $index => $id) {
            if (! in_array($id, $visible, true)) {
                $violations[] = new BuildDataViolation(BuildDataError::PalettePieceUnavailable, $index);
            }
        }

        return array_slice($violations, 0, (int) config('builds.max_errors'));
    }
}
