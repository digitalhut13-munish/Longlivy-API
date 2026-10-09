<?php

namespace App\Services\Energy;

use App\Models\Activity;
use App\Models\DailyEnergyBalance;
use App\Models\EnergyExpenditure;
use App\Models\Goal;
use App\Models\Meal;
use App\Models\User;
use Carbon\Carbon;

/**
 * Daily energy balance: intake against the merged consumption
 * components (basal rate, everyday activity, sport, imports).
 *
 * The balance is a stored projection - it is refreshed by listeners
 * when food, activity, body weight, the profile or the calorie
 * target changes, so a calculation never has to be triggered
 * manually.
 */
class EnergyBalanceService
{
    public function __construct(
        private readonly CalorieCalculationEngine $engine
    ) {
    }

    /**
     * Recalculate and persist the balance for one day.
     */
    public function recompute(User $user, string $date): DailyEnergyBalance
    {
        $this->syncCalculatedComponents($user, $date);

        $components = EnergyExpenditure::where('user_id', $user->id)
            ->whereDate('date', $date)
            ->get()
            ->groupBy('component');

        $sum = fn (string $component): float => round(
            (float) ($components->get($component)?->sum('calories') ?? 0),
            2
        );

        $bmr = $sum('bmr');
        $everyday = $sum('everyday_activity');
        $sport = $sum('sport_activity');
        $imported = $sum('imported_activity');
        $manual = $sum('manual_activity');

        $totalConsumption = round(
            $bmr + $everyday + $sport + $imported + $manual,
            2
        );

        $intake = round(
            (float) Meal::where('user_id', $user->id)
                ->whereDate('date', $date)
                ->sum('total_calories'),
            2
        );

        $goal = $this->calorieGoal($user, $date);

        $difference = round($intake - $totalConsumption, 2);

        $remaining = null;
        $overBy = null;
        $status = 'no_goal';

        if ($goal !== null) {
            if ($intake > $goal + 0.001) {
                $status = 'exceeded';
                $overBy = round($intake - $goal, 2);
            } elseif ($intake >= $goal - 0.001) {
                $status = 'met';
                $remaining = 0.0;
            } else {
                $status = 'below';
                $remaining = round($goal - $intake, 2);
            }
        }

        return DailyEnergyBalance::updateOrCreate(
            [
                'user_id' => $user->id,
                'date' => $date,
            ],
            [
                'bmr' => $bmr,
                'everyday_activity' => $everyday,
                'sport_activity' => $sport,
                'imported_activity' => $imported,
                'manual_activity' => $manual,
                'total_consumption' => $totalConsumption,
                'intake' => $intake,
                'calorie_goal' => $goal,
                'difference' => $difference,
                'remaining' => $remaining,
                'over_by' => $overBy,
                'status' => $status,
                'computed_at' => now(),
            ]
        );
    }

    /**
     * Stored balance, computed on first access.
     */
    public function get(User $user, string $date): DailyEnergyBalance
    {
        $existing = DailyEnergyBalance::where('user_id', $user->id)
            ->whereDate('date', $date)
            ->first();

        if ($existing !== null) {
            return $existing;
        }

        return $this->recompute($user, $date);
    }

    /**
     * Refresh the calculated components (BMR, everyday activity)
     * for one day. Values are replaced, never accumulated, and the
     * method/version used is stored with each row.
     */
    public function syncCalculatedComponents(
        User $user,
        string $date
    ): void {
        $method = $this->engine->method();

        if (! $this->engine->isReady($user)) {
            EnergyExpenditure::where('user_id', $user->id)
                ->whereDate('date', $date)
                ->whereIn('component', EnergyExpenditure::CALCULATED_COMPONENTS)
                ->delete();

            return;
        }

        $values = [
            'bmr' => (float) $this->engine->bmr($user),
            'everyday_activity' => (float) $this->engine->everydayActivity($user),
        ];

        foreach ($values as $component => $calories) {
            EnergyExpenditure::updateOrCreate(
                [
                    'user_id' => $user->id,
                    'date' => $date,
                    'component' => $component,
                    'source' => 'longlivy_calculated',
                    'external_id' => '',
                ],
                [
                    'calories' => $calories,
                    'calculation_method' => $method['method'],
                    'calculation_version' => $method['version'],
                    'calculated_at' => now(),
                ]
            );
        }
    }

    /**
     * Store one activity's calories in the expenditure projection so
     * the daily balance reflects sport/manual/imported work, then
     * refresh the covering day.
     */
    public function syncActivity(
        User $user,
        Activity $activity
    ): void {
        $date = $this->activityDate($user, $activity);

        if ($activity->started_at === null) {
            return;
        }

        EnergyExpenditure::updateOrCreate(
            [
                'user_id' => $user->id,
                'date' => $date,
                'component' => $activity->componentForBalance(),
                'source' => 'activity',
                'external_id' => $activity->client_id,
            ],
            [
                'calories' => $activity->calories_kcal,
                'calculation_method' => $activity->calculation_method,
                'calculation_version' => '1.0',
                'provider' => 'longlivy_activity',
                'calculated_at' => now(),
                'meta' => [
                    'type' => $activity->type,
                    'started_at' => $activity->started_at->toIso8601String(),
                    'duration_seconds' => $activity->active_seconds,
                ],
            ]
        );

        $this->recompute($user, $date);
    }

    /**
     * Remove an activity's calories from the projection.
     */
    public function removeActivity(
        User $user,
        Activity $activity
    ): void {
        $date = $this->activityDate($user, $activity);

        EnergyExpenditure::where('user_id', $user->id)
            ->where('date', $date)
            ->where('component', $activity->componentForBalance())
            ->where('source', 'activity')
            ->where('external_id', $activity->client_id)
            ->delete();

        $this->recompute($user, $date);
    }

    private function activityDate(
        User $user,
        Activity $activity
    ): string {
        $timezone = $user->profile?->timezone() ?? 'UTC';

        return $activity->started_at
            ->setTimezone($timezone)
            ->toDateString();
    }

    /**
     * Active daily calorie target covering the given date, or null
     * when the user has not defined one.
     */
    private function calorieGoal(User $user, string $date): ?float
    {
        $goal = Goal::where('user_id', $user->id)
            ->where('goal_type', 'nutrition_calories')
            ->where('period', 'day')
            ->where('active', true)
            ->whereDate('start_date', '<=', $date)
            ->where(function ($builder) use ($date) {
                $builder->whereNull('end_date')
                    ->orWhereDate('end_date', '>=', $date);
            })
            ->orderByDesc('source')
            ->first();

        return $goal !== null ? (float) $goal->target_value : null;
    }
}
