<?php

namespace App\Services\Nutrition;

use App\Models\Goal;
use App\Models\Meal;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

class NutritionDayService
{
    /**
     * Nutrient key => goal_type used in the generic goals module.
     *
     * @var array<string, string>
     */
    public const GOAL_TYPES = [
        'calories' => 'nutrition_calories',
        'protein' => 'nutrition_protein',
        'carbohydrates' => 'nutrition_carbs',
        'fat' => 'nutrition_fat',
        'fiber' => 'nutrition_fiber',
    ];

    /**
     * @var array<string, string>
     */
    public const UNITS = [
        'calories' => 'kcal',
        'protein' => 'g',
        'carbohydrates' => 'g',
        'fat' => 'g',
        'fiber' => 'g',
    ];

    /**
     * @return array<string, mixed>
     */
    public function day(User $user, string $date): array
    {
        /** @var Collection<int, Meal> $meals */
        $meals = $user->meals()
            ->with('items')
            ->whereDate('date', $date)
            ->orderBy('logged_at')
            ->get();

        $intake = [
            'calories' => round((float) $meals->sum('total_calories'), 2),
            'protein' => round((float) $meals->sum('total_protein'), 2),
            'carbohydrates' => round(
                (float) $meals->sum('total_carbohydrates'),
                2
            ),
            'fat' => round((float) $meals->sum('total_fat'), 2),
            'fiber' => round((float) $meals->sum('total_fiber'), 2),
            'sodium' => round((float) $meals->sum('total_sodium'), 2),
        ];

        $goals = $this->goalsForDate($user, $date);

        $nutrients = [];

        foreach (self::GOAL_TYPES as $nutrient => $goalType) {
            $nutrients[] = $this->buildNutrient(
                $nutrient,
                $intake[$nutrient] ?? 0.0,
                $goals[$goalType] ?? null
            );
        }

        $lastFoods = $meals
            ->sortByDesc('logged_at')
            ->flatMap(fn (Meal $meal) => $meal->items)
            ->take(5)
            ->values();

        return [
            'date' => $date,
            'intake' => $intake,
            'nutrients' => $nutrients,
            'meals' => $meals,
            'last_foods' => $lastFoods,
        ];
    }

    /**
     * @return array<string, Goal>
     */
    private function goalsForDate(User $user, string $date): array
    {
        $goals = Goal::where('user_id', $user->id)
            ->whereIn('goal_type', array_values(self::GOAL_TYPES))
            ->where('period', 'day')
            ->where('active', true)
            ->whereDate('start_date', '<=', $date)
            ->where(function ($builder) use ($date) {
                $builder->whereNull('end_date')
                    ->orWhereDate('end_date', '>=', $date);
            })
            ->get()
            ->keyBy('goal_type');

        return $goals->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function buildNutrient(
        string $nutrient,
        float $current,
        ?Goal $goal
    ): array {
        $unit = self::UNITS[$nutrient];

        if ($goal === null) {
            return [
                'nutrient' => $nutrient,
                'unit' => $unit,
                'current' => $current,
                'target' => null,
                'source' => null,
                'status' => 'no_goal',
                'remaining' => null,
                'over_by' => null,
                'percent' => null,
            ];
        }

        $target = (float) $goal->target_value;

        if ($current > $target + 0.001) {
            $status = 'exceeded';
            $remaining = null;
            $overBy = round($current - $target, 2);
        } elseif ($current >= $target - 0.001) {
            $status = 'met';
            $remaining = 0.0;
            $overBy = null;
        } else {
            $status = 'below';
            $remaining = round($target - $current, 2);
            $overBy = null;
        }

        return [
            'nutrient' => $nutrient,
            'unit' => $unit,
            'current' => $current,
            'target' => $target,
            'source' => $goal->source,
            'status' => $status,
            'remaining' => $remaining,
            'over_by' => $overBy,
            'percent' => $target > 0
                ? round(($current / $target) * 100, 1)
                : null,
        ];
    }
}
