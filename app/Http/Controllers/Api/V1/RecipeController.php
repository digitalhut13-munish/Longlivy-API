<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Nutrition\StoreRecipeRequest;
use App\Http\Requests\Api\V1\Nutrition\UpdateRecipeRequest;
use App\Http\Resources\RecipeResource;
use App\Models\Recipe;
use App\Services\Nutrition\RecipeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RecipeController extends Controller
{
    public function __construct(
        private readonly RecipeService $recipeService
    ) {}

    /**
     * List the user's recipes.
     */
    public function index(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $recipes = $this->recipeService->getUserRecipes(
            $request->user(),
            $filters
        );

        return response()->json([
            'success' => true,
            'message' => 'Recipes retrieved successfully.',
            'data' => [
                'recipes' => RecipeResource::collection(
                    $recipes->items()
                ),
                'meta' => [
                    'current_page' => $recipes->currentPage(),
                    'per_page' => $recipes->perPage(),
                    'total' => $recipes->total(),
                    'last_page' => $recipes->lastPage(),
                ],
            ],
        ]);
    }

    /**
     * Create a recipe.
     */
    public function store(
        StoreRecipeRequest $request
    ): JsonResponse {
        $recipe = $this->recipeService->create(
            $request->user(),
            $request->validated()
        );

        return response()->json([
            'success' => true,
            'message' => 'Recipe created successfully.',
            'data' => [
                'recipe' => new RecipeResource($recipe),
            ],
        ], 201);
    }

    /**
     * Get a recipe.
     */
    public function show(
        Request $request,
        Recipe $recipe
    ): JsonResponse {
        $this->authorize('view', $recipe);

        $recipe->load('items');

        return response()->json([
            'success' => true,
            'message' => 'Recipe retrieved successfully.',
            'data' => [
                'recipe' => new RecipeResource($recipe),
            ],
        ]);
    }

    /**
     * Update a recipe.
     */
    public function update(
        UpdateRecipeRequest $request,
        Recipe $recipe
    ): JsonResponse {
        $this->authorize('update', $recipe);

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

        $recipe = $this->recipeService->update($recipe, $data);

        return response()->json([
            'success' => true,
            'message' => 'Recipe updated successfully.',
            'data' => [
                'recipe' => new RecipeResource($recipe),
            ],
        ]);
    }

    /**
     * Delete a recipe.
     */
    public function destroy(
        Request $request,
        Recipe $recipe
    ): JsonResponse {
        $this->authorize('delete', $recipe);

        $this->recipeService->delete($recipe);

        return response()->json([
            'success' => true,
            'message' => 'Recipe deleted successfully.',
        ]);
    }
}
