<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\EnergyExpenditure;
use App\Services\Energy\CalorieCalculationEngine;
use App\Services\Energy\EnergyBalanceService;
use App\Services\Goal\NutritionGoalService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EnergyController extends Controller
{
    public function __construct(
        private readonly CalorieCalculationEngine $engine,
        private readonly EnergyBalanceService $balanceService,
        private readonly NutritionGoalService $goalService
    ) {
    }

    /**
     * Daily energy balance: intake against consumption.
     */
    public function balance(Request $request): JsonResponse
    {
        $data = $request->validate([
            'date' => ['nullable', 'date'],
        ]);

        $timezone = $request->user()->profile?->timezone() ?? 'UTC';

        $date = $data['date']
            ?? Carbon::now($timezone)->toDateString();

        $balance = $this->balanceService->recompute(
            $request->user(),
            $date
        );

        $components = EnergyExpenditure::where(
            'user_id',
            $request->user()->id
        )
            ->whereDate('date', $date)
            ->orderBy('component')
            ->get()
            ->map(fn (EnergyExpenditure $row) => [
                'component' => $row->component,
                'calories' => (float) $row->calories,
                'source' => $row->source,
                'calculation_method' => $row->calculation_method,
                'calculation_version' => $row->calculation_version,
                'provider' => $row->provider,
                'calculated_at' => $row->calculated_at?->toIso8601String(),
            ])
            ->values();

        $intake = (float) $balance->intake;
        $goal = $balance->calorie_goal !== null
            ? (float) $balance->calorie_goal
            : null;

        return response()->json([
            'success' => true,
            'message' => 'Energy balance retrieved successfully.',
            'data' => [
                'date' => $balance->date->toDateString(),

                'consumption' => [
                    'bmr' => (float) $balance->bmr,
                    'everyday_activity' => (float) $balance->everyday_activity,
                    'sport_activity' => (float) $balance->sport_activity,
                    'imported_activity' => (float) $balance->imported_activity,
                    'manual_activity' => (float) $balance->manual_activity,
                    'total' => (float) $balance->total_consumption,
                ],

                'intake' => $intake,

                'goal' => [
                    'calories' => $goal,
                    'status' => $balance->status,
                    'remaining' => $balance->remaining !== null
                        ? (float) $balance->remaining
                        : null,
                    'over_by' => $balance->over_by !== null
                        ? (float) $balance->over_by
                        : null,
                ],

                // intake - consumption; negative means the day is a
                // net deficit.
                'difference' => (float) $balance->difference,

                'ring' => [
                    'current' => $intake,
                    'target' => $goal,
                    'percent' => $goal !== null && $goal > 0
                        ? round(($intake / $goal) * 100, 1)
                        : null,
                    'state' => $balance->status,
                ],

                'components' => $components,

                'computed_at' => $balance->computed_at?->toIso8601String(),
            ],
        ]);
    }

    /**
     * Basal metabolic rate with the inputs and algorithm used.
     */
    public function bmr(Request $request): JsonResponse
    {
        $user = $request->user();
        $method = $this->engine->method();

        return response()->json([
            'success' => true,
            'message' => 'Basal metabolic rate retrieved successfully.',
            'data' => [
                'bmr' => $this->engine->bmr($user),
                'tdee' => $this->engine->tdee($user),
                'everyday_activity' => $this->engine->everydayActivity($user),
                'activity_factor' => $this->engine->activityFactor($user),
                'method' => $method['method'],
                'version' => $method['version'],
                'inputs' => $this->engine->inputs($user),
                'missing_inputs' => $this->engine->missingInputs($user),
                'ready' => $this->engine->isReady($user),
            ],
        ]);
    }

    /**
     * Calculate the personal nutrition targets automatically.
     */
    public function calculateTargets(Request $request): JsonResponse
    {
        $data = $request->validate([
            'direction' => ['nullable', 'in:maintain,lose,gain'],
        ]);

        $result = $this->goalService->calculate(
            $request->user(),
            $data['direction'] ?? 'maintain'
        );

        return response()->json([
            'success' => true,
            'message' => 'Nutrition targets calculated successfully.',
            'data' => $result,
        ]);
    }
}
