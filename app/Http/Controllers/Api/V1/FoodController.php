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
     *
     * The search term uses the documented "q" parameter. Legacy
     * "limit"/"mine" parameters are kept working alongside the
     * paginated page/per_page API.
     */
    public function index(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:191', 'min:2'],
            'scope' => ['nullable', 'in:all,catalog,mine'],
            'category' => ['nullable', 'string', 'max:191'],
            'brand' => ['nullable', 'string', 'max:191'],
            'category_id' => ['nullable', 'integer'],
            'barcode' => ['nullable', 'string', 'max:64'],
            'mine' => ['nullable', 'boolean'],
            'sort' => ['nullable', 'in:relevance,name,calories,recent'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $foods = $this->foodService->search(
            $request->user(),
            $filters
        );

        $foods->getCollection()->load('category');

        return response()->json([
            'success' => true,
            'message' => 'Foods retrieved successfully.',
            'data' => [
                'foods' => FoodResource::collection($foods->items()),
                'meta' => [
                    'current_page' => $foods->currentPage(),
                    'per_page' => $foods->perPage(),
                    'total' => $foods->total(),
                    'last_page' => $foods->lastPage(),
                ],
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
     *
     * Codes must be 8, 12, 13 or 14 digits (EAN/UPC). A local miss is
     * answered against the external product database and the result is
     * cached as a shared catalog food so other users benefit too.
     */
    public function barcode(
        Request $request,
        string $barcode,
        BarcodeLookupService $lookup
    ): JsonResponse {
        if (! preg_match('/^\d{8}$|^\d{12}$|^\d{13}$|^\d{14}$/', $barcode)) {
            return response()->json([
                'success' => false,
                'message' => 'The barcode must be 8, 12, 13 or 14 digits.',
                'errors' => [
                    'code' => [
                        'The barcode must be 8, 12, 13 or 14 digits.',
                    ],
                ],
            ], 422);
        }

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

        $isLiquid = in_array(
            strtolower((string) ($remote['unit'] ?? '')),
            ['ml', 'l'],
            true
        );

        $food = $this->foodService->findOrCreateCatalog([
            'name' => $remote['name'] ?? 'Unknown product',
            'brand' => $remote['brand'] ?? null,
            'barcode' => $barcode,
            'base_unit' => $isLiquid ? 'ml' : 'g',
            'base_amount' => 100,
            'calories' => $remote['calories'] ?? 0,
            'protein' => $remote['protein'] ?? 0,
            'carbohydrates' => $remote['carbohydrates'] ?? 0,
            'fat' => $remote['fat'] ?? 0,
            'fiber' => $remote['fiber'] ?? null,
            'sugar' => $remote['sugar'] ?? null,
            'sodium' => $remote['sodium'] ?? null,
            'verified' => true,
            'is_custom' => false,
            'source' => $remote['source'] ?? 'open_food_facts',
            'external_id' => $barcode,
        ]);

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
