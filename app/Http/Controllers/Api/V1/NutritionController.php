<?php

namespace App\Http\Controllers\Api\V1;

use App\Contracts\Integrations\MealRecognitionService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Nutrition\RecognizeMealRequest;
use App\Http\Requests\Api\V1\Nutrition\StoreNutritionLogRequest;
use App\Http\Resources\MealItemResource;
use App\Http\Resources\MealResource;
use App\Services\Fasting\FastingService;
use App\Services\Nutrition\MealService;
use App\Services\Nutrition\NutritionDayService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NutritionController extends Controller
{
    public function __construct(
        private readonly MealService $mealService,
        private readonly NutritionDayService $dayService,
        private readonly FastingService $fastingService,
        private readonly MealRecognitionService $recognition
    ) {}

    /**
     * Aggregated nutrition for a single day.
     */
    public function day(Request $request): JsonResponse
    {
        $data = $request->validate([
            'date' => ['nullable', 'date'],
        ]);

        $timezone = $request->user()->profile?->timezone() ?? 'UTC';

        $date = isset($data['date'])
            ? Carbon::parse($data['date'])->toDateString()
            : Carbon::now($timezone)->toDateString();

        $day = $this->dayService->day($request->user(), $date);

        return response()->json([
            'success' => true,
            'message' => 'Nutrition day retrieved successfully.',
            'data' => [
                'date' => $day['date'],
                'intake' => $day['intake'],
                'nutrients' => $day['nutrients'],
                'meals' => MealResource::collection($day['meals']),
                'last_foods' => MealItemResource::collection(
                    $day['last_foods']
                ),
            ],
        ]);
    }

    /**
     * Quick log: one food into one meal.
     */
    public function log(
        StoreNutritionLogRequest $request
    ): JsonResponse {
        $meal = $this->mealService->log(
            $request->user(),
            $request->validated()
        );

        return response()->json([
            'success' => true,
            'message' => 'Food logged successfully.',
            'data' => [
                'meal' => new MealResource($meal),
                'active_fasting' => $this->fastingSummary($request),
            ],
        ], 201);
    }

    /**
     * Draft recognition from photo, voice or free text.
     *
     * The draft is never persisted: the client must send the confirmed
     * entries through POST /nutrition/log.
     */
    public function recognize(
        RecognizeMealRequest $request
    ): JsonResponse {
        $data = $request->validated();

        $draft = $this->recognition->recognize($data['type'], $data);

        return response()->json([
            'success' => true,
            'message' => 'Meal draft created. Review it before saving.',
            'data' => [
                'draft' => $draft,
                'needs_review' => true,
                'persisted' => false,
            ],
        ]);
    }

    /**
     * @return array<string, mixed>|null
     */
    private function fastingSummary(Request $request): ?array
    {
        $fasting = $this->fastingService->getActive($request->user());

        if ($fasting === null) {
            return null;
        }

        return [
            'id' => $fasting->id,
            'started_at' => $fasting->started_at?->toIso8601String(),
            'planned_hours' => $fasting->planned_hours,
            'requires_confirmation' => true,
        ];
    }
}
