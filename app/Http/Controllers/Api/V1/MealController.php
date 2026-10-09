<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Nutrition\StoreMealItemRequest;
use App\Http\Requests\Api\V1\Nutrition\StoreMealRequest;
use App\Http\Requests\Api\V1\Nutrition\UpdateMealItemRequest;
use App\Http\Requests\Api\V1\Nutrition\UpdateMealRequest;
use App\Http\Resources\MealItemResource;
use App\Http\Resources\MealResource;
use App\Models\Meal;
use App\Models\MealItem;
use App\Services\Fasting\FastingService;
use App\Services\Nutrition\MealService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MealController extends Controller
{
    public function __construct(
        private readonly MealService $mealService,
        private readonly FastingService $fastingService
    ) {}

    /**
     * List meals.
     */
    public function index(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'date' => ['nullable', 'date'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date'],
            'meal_type' => ['nullable', 'string'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $meals = $this->mealService->getMeals(
            $request->user(),
            $filters
        );

        return response()->json([
            'success' => true,
            'message' => 'Meals retrieved successfully.',
            'data' => [
                'meals' => MealResource::collection($meals->items()),
                'meta' => [
                    'current_page' => $meals->currentPage(),
                    'per_page' => $meals->perPage(),
                    'total' => $meals->total(),
                    'last_page' => $meals->lastPage(),
                ],
            ],
        ]);
    }

    /**
     * Create a meal.
     */
    public function store(
        StoreMealRequest $request
    ): JsonResponse {
        $meal = $this->mealService->create(
            $request->user(),
            $request->validated()
        );

        return response()->json([
            'success' => true,
            'message' => 'Meal created successfully.',
            'data' => [
                'meal' => new MealResource($meal),
                'active_fasting' => $this->fastingSummary($request),
            ],
        ], 201);
    }

    /**
     * Get a meal.
     */
    public function show(
        Request $request,
        Meal $meal
    ): JsonResponse {
        $this->authorize('view', $meal);

        $meal->load('items');

        return response()->json([
            'success' => true,
            'message' => 'Meal retrieved successfully.',
            'data' => [
                'meal' => new MealResource($meal),
            ],
        ]);
    }

    /**
     * Update a meal.
     */
    public function update(
        UpdateMealRequest $request,
        Meal $meal
    ): JsonResponse {
        $this->authorize('update', $meal);

        $data = $request->validated();

        if ($data === []) {
            return response()->json([
                'success' => false,
                'message' => 'No updatable fields received.',
                'errors' => [
                    'body' => [
                        'Send the payload as JSON with '
                        .'Content-Type: application/json. Note that '
                        .'multipart/form-data is not supported for PUT.',
                    ],
                ],
            ], 422);
        }

        $meal = $this->mealService->update($meal, $data);

        return response()->json([
            'success' => true,
            'message' => 'Meal updated successfully.',
            'data' => [
                'meal' => new MealResource($meal),
            ],
        ]);
    }

    /**
     * Delete a meal.
     */
    public function destroy(
        Request $request,
        Meal $meal
    ): JsonResponse {
        $this->authorize('delete', $meal);

        $this->mealService->delete($meal);

        return response()->json([
            'success' => true,
            'message' => 'Meal deleted successfully.',
        ]);
    }

    /**
     * Add an item to a meal.
     */
    public function addItem(
        StoreMealItemRequest $request,
        Meal $meal
    ): JsonResponse {
        $this->authorize('update', $meal);

        $item = $this->mealService->addItem(
            $meal,
            $request->validated()
        );

        return response()->json([
            'success' => true,
            'message' => 'Meal item saved successfully.',
            'data' => [
                'item' => new MealItemResource($item),
                'meal' => new MealResource($meal->fresh('items')),
            ],
        ], 201);
    }

    /**
     * Update a meal item.
     */
    public function updateItem(
        UpdateMealItemRequest $request,
        Meal $meal,
        MealItem $item
    ): JsonResponse {
        $this->authorize('update', $meal);

        if ($item->meal_id !== $meal->id) {
            abort(404);
        }

        $data = $request->validated();

        if ($data === []) {
            return response()->json([
                'success' => false,
                'message' => 'No updatable fields received.',
                'errors' => [
                    'body' => [
                        'Send the payload as JSON with '
                        .'Content-Type: application/json. Note that '
                        .'multipart/form-data is not supported for PUT.',
                    ],
                ],
            ], 422);
        }

        $item = $this->mealService->updateItem($meal, $item, $data);

        return response()->json([
            'success' => true,
            'message' => 'Meal item updated successfully.',
            'data' => [
                'item' => new MealItemResource($item),
                'meal' => new MealResource($meal->fresh('items')),
            ],
        ]);
    }

    /**
     * Delete a meal item.
     */
    public function deleteItem(
        Request $request,
        Meal $meal,
        MealItem $item
    ): JsonResponse {
        $this->authorize('update', $meal);

        if ($item->meal_id !== $meal->id) {
            abort(404);
        }

        $this->mealService->deleteItem($meal, $item);

        return response()->json([
            'success' => true,
            'message' => 'Meal item deleted successfully.',
            'data' => [
                'meal' => new MealResource($meal->fresh('items')),
            ],
        ]);
    }

    /**
     * Active fast information so the client can ask the user whether
     * the meal should end it. The API never ends a fast on its own.
     *
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
