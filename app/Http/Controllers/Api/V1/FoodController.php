<?php

namespace App\Http\Controllers\Api\V1;

use App\Contracts\Integrations\BarcodeLookupService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Nutrition\StoreFoodRequest;
use App\Http\Requests\Api\V1\Nutrition\UpdateFoodRequest;
use App\Http\Resources\FoodResource;
use App\Models\Food;
use App\Services\Nutrition\FoodService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FoodController extends Controller
{
    public function __construct(
        private readonly FoodService $foodService
    ) {}

    /**
     * Search the food database.
     */
    public function index(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:191'],
            'brand' => ['nullable', 'string', 'max:191'],
            'category_id' => ['nullable', 'integer'],
            'barcode' => ['nullable', 'string', 'max:64'],
            'mine' => ['nullable', 'boolean'],
            'sort' => ['nullable', 'in:relevance,name,calories,recent'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $foods = $this->foodService->search(
            $request->user(),
            $filters
        );

        return response()->json([
            'success' => true,
            'message' => 'Foods retrieved successfully.',
            'data' => [
                'foods' => FoodResource::collection($foods),
            ],
        ]);
    }

    /**
     * Create a custom food.
     */
    public function store(
        StoreFoodRequest $request
    ): JsonResponse {
        $food = $this->foodService->create(
            $request->user(),
            $request->validated()
        );

        return response()->json([
            'success' => true,
            'message' => 'Food created successfully.',
            'data' => [
                'food' => new FoodResource($food),
            ],
        ], 201);
    }

    /**
     * Food categories.
     */
    public function categories(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'Food categories retrieved successfully.',
            'data' => [
                'categories' => $this->foodService->categories(),
            ],
        ]);
    }

    /**
     * Resolve a scanned barcode.
     */
    public function barcode(
        Request $request,
        string $barcode,
        BarcodeLookupService $lookup
    ): JsonResponse {
        $local = $this->foodService->findByBarcode(
            $request->user(),
            $barcode
        );

        if ($local !== null) {
            return response()->json([
                'success' => true,
                'found' => true,
                'message' => 'Product found.',
                'data' => [
                    'food' => new FoodResource($local),
                ],
            ]);
        }

        $remote = $lookup->lookup($barcode);

        if ($remote === null) {
            return response()->json([
                'success' => false,
                'found' => false,
                'message' => 'Barcode not known. Enter the product '
                    .'manually.',
                'data' => [
                    'barcode' => $barcode,
                ],
            ], 404);
        }

        $food = $this->foodService->create(
            $request->user(),
            [
                'name' => $remote['name'] ?? 'Unknown product',
                'brand' => $remote['brand'] ?? null,
                'barcode' => $barcode,
                'calories' => $remote['calories'] ?? 0,
                'protein' => $remote['protein'] ?? 0,
                'carbohydrates' => $remote['carbohydrates'] ?? 0,
                'fat' => $remote['fat'] ?? 0,
                'fiber' => $remote['fiber'] ?? null,
                'sugar' => $remote['sugar'] ?? null,
                'sodium' => $remote['sodium'] ?? null,
                'source' => 'barcode_database',
            ]
        );

        return response()->json([
            'success' => true,
            'found' => true,
            'message' => 'Product found.',
            'data' => [
                'food' => new FoodResource($food),
                'needs_review' => true,
            ],
        ], 201);
    }

    /**
     * Get a food.
     */
    public function show(
        Request $request,
        Food $food
    ): JsonResponse {
        $this->authorize('view', $food);

        return response()->json([
            'success' => true,
            'message' => 'Food retrieved successfully.',
            'data' => [
                'food' => new FoodResource($food),
            ],
        ]);
    }

    /**
     * Update a custom food.
     */
    public function update(
        UpdateFoodRequest $request,
        Food $food
    ): JsonResponse {
        $this->authorize('update', $food);

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

        $food = $this->foodService->update($food, $data);

        return response()->json([
            'success' => true,
            'message' => 'Food updated successfully.',
            'data' => [
                'food' => new FoodResource($food),
            ],
        ]);
    }

    /**
     * Delete a custom food.
     */
    public function destroy(
        Request $request,
        Food $food
    ): JsonResponse {
        $this->authorize('delete', $food);

        $this->foodService->delete($food);

        return response()->json([
            'success' => true,
            'message' => 'Food deleted successfully.',
        ]);
    }
}
