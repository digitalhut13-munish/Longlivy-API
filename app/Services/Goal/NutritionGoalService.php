<?php

namespace App\Services\Goal;

use App\Models\Goal;
use App\Models\User;
use App\Services\Energy\CalorieCalculationEngine;
use App\Services\Energy\EnergyBalanceService;
use Carbon\Carbon;
use Illuminate\Validation\ValidationException;

/**
 * Automatic nutrition targets.
 *
 * Targets are stored in the generic goals module with
 * source = longlivy_calculated so a target the user set by hand
 * (source = manual) is never overwritten by a calculation.
 */
class NutritionGoalService
{
    /**
     * goal_type => [label, unit] for every calculated target.
     *
     * @var array<string, array{0: string, 1: string}>
     */
    private const TARGETS = [
        'nutrition_calories' => ['Calories', 'kcal'],
        'nutrition_protein' => ['Protein', 'g'],
        'nutrition_carbs' => ['Carbohydrates', 'g'],
        'nutrition_fat' => ['Fat', 'g'],
        'nutrition_fiber' => ['Fiber', 'g'],
    ];

    public function __construct(
        private readonly CalorieCalculationEngine $engine,
        private readonly EnergyBalanceService $balanceService
    ) {
    }

    /**
     * Calculate and store the daily nutrition targets.
     *
     * @param  string  $direction  maintain | lose | gain
     * @param  float|null  $weeklyChangeKg  weekly pace (0.25-1.0 kg/week);
     *                             overrides the fixed kcal adjustment with
     *                             weekly_change_kg * 7700 / 7 per day
     * @param  bool  $overwriteManual  when set, manual targets are
     *                             replaced instead of being skipped
     * @return array<string, mixed>
     *
     * @throws ValidationException when the profile is incomplete.
     */
    public function calculate(
        User $user,
        string $direction = 'maintain',
        ?float $weeklyChangeKg = null,
        bool $overwriteManual = false
    ): array {
        $missing = $this->engine->missingInputs($user);

        if ($missing !== []) {
            throw ValidationException::withMessages([
                'profile' => 'The profile is incomplete. Fill in: '
                    . implode(', ', $missing) . '.',
            ]);
        }

        $tdee = (float) $this->engine->tdee($user);

        if ($weeklyChangeKg !== null && $weeklyChangeKg > 0) {
            $delta = round($weeklyChangeKg * 7700 / 7);
        } else {
            $delta = match ($direction) {
                'lose' => (int) config('longlivy.calorie.deficit'),
                'gain' => (int) config('longlivy.calorie.surplus'),
                default => 0,
            };
        }

        $adjustment = match ($direction) {
            'lose' => -abs($delta),
            'gain' => abs($delta),
            default => 0,
        };

        $calories = max(1200, round($tdee + $adjustment));

        $macros = $this->engine->macroTargets($calories);

        $values = [
            'nutrition_calories' => ['value' => $calories, 'unit' => 'kcal'],
            'nutrition_protein' => [
                'value' => $macros['protein'],
                'unit' => 'g',
            ],
            'nutrition_carbs' => [
                'value' => $macros['carbohydrates'],
                'unit' => 'g',
            ],
            'nutrition_fat' => ['value' => $macros['fat'], 'unit' => 'g'],
            'nutrition_fiber' => ['value' => $macros['fiber'], 'unit' => 'g'],
        ];

        $results = [];

        foreach ($values as $goalType => $computed) {
            $results[$goalType] = $this->store(
                $user,
                $goalType,
                (float) $computed['value'],
                $computed['unit'],
                $overwriteManual
            );
        }

        // The calorie target feeds the daily balance, so refresh
        // today immediately instead of waiting for the next event.
        $this->balanceService->recompute(
            $user,
            Carbon::now($user->profile?->timezone() ?? 'UTC')
                ->toDateString()
        );

        return [
            'direction' => $direction,
            'tdee' => $tdee,
            'adjustment' => $adjustment,
            'method' => $this->engine->method(),
            'targets' => $results,
        ];
    }

    /**
     * @param  bool  $overwriteManual
     * @return array<string, mixed>
     */
    private function store(
        User $user,
        string $goalType,
        float $value,
        string $unit,
        bool $overwriteManual = false
    ): array {
        $existing = Goal::where('user_id', $user->id)
            ->where('goal_type', $goalType)
            ->where('period', 'day')
            ->where('active', true)
            ->orderByDesc('id')
            ->first();

        if ($existing !== null
            && $existing->source === 'manual'
            && ! $overwriteManual
        ) {
            return [
                'goal_type' => $goalType,
                'value' => $this->normalizeValue(
                    (float) $existing->target_value
                ),
                'unit' => $existing->unit,
                'source' => 'manual',
                'updated' => false,
                'reason' => 'manual_override',
            ];
        }

        $goal = $existing ?? new Goal([
            'user_id' => $user->id,
            'goal_type' => $goalType,
        ]);

        $goal->fill([
            'user_id' => $user->id,
            'goal_type' => $goalType,
            'name' => self::TARGETS[$goalType][0],
            'target_value' => $value,
            'unit' => $unit,
            'period' => 'day',
            'start_date' => now()->toDateString(),
            'end_date' => null,
            'active' => true,
            'source' => 'longlivy_calculated',
        ]);

        $goal->save();

        return [
            'goal_type' => $goalType,
            'value' => $this->normalizeValue($value),
            'unit' => $unit,
            'source' => 'longlivy_calculated',
            'updated' => true,
            'reason' => null,
        ];
    }

    /**
     * Whole values are emitted as integers so consumers and contract
     * tests comparing 'value' run against a stable JSON number.
     */
    private function normalizeValue(float $value): int|float
    {
        if ($value == floor($value)) {
            return (int) $value;
        }

        return round($value, 2);
    }
}
