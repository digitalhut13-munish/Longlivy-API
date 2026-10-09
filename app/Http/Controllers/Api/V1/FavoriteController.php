<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Nutrition\StoreFavoriteRequest;
use App\Http\Resources\FavoriteResource;
use App\Models\Favorite;
use App\Services\Nutrition\FavoriteService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FavoriteController extends Controller
{
    public function __construct(
        private readonly FavoriteService $favoriteService
    ) {}

    /**
     * List favorites, optionally filtered by type.
     */
    public function index(Request $request): JsonResponse
    {
        $data = $request->validate([
            'type' => ['nullable', 'in:food,meal,meditation'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $favorites = $this->favoriteService->list(
            $request->user(),
            $data['type'] ?? null,
            $data
        );

        return response()->json([
            'success' => true,
            'message' => 'Favorites retrieved successfully.',
            'data' => [
                'favorites' => FavoriteResource::collection(
                    $favorites->items()
                ),
                'meta' => [
                    'current_page' => $favorites->currentPage(),
                    'per_page' => $favorites->perPage(),
                    'total' => $favorites->total(),
                    'last_page' => $favorites->lastPage(),
                ],
            ],
        ]);
    }

    /**
     * Add a favorite.
     */
    public function store(
        StoreFavoriteRequest $request
    ): JsonResponse {
        $data = $request->validated();

        $favorite = $this->favoriteService->add(
            $request->user(),
            $data['type'],
            (int) $data['id']
        );

        return response()->json([
            'success' => true,
            'message' => 'Favorite saved successfully.',
            'data' => [
                'favorite' => new FavoriteResource($favorite->load('favoritable')),
            ],
        ], 201);
    }

    /**
     * Remove a favorite.
     */
    public function destroy(
        Request $request,
        Favorite $favorite
    ): JsonResponse {
        $this->authorize('delete', $favorite);

        $this->favoriteService->remove($request->user(), $favorite);

        return response()->json([
            'success' => true,
            'message' => 'Favorite removed successfully.',
        ]);
    }
}
