<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Meditation\StoreMeditationCategoryRequest;
use App\Http\Requests\Api\V1\Meditation\StoreMeditationRequest;
use App\Http\Resources\MeditationCategoryResource;
use App\Http\Resources\MeditationReminderResource;
use App\Http\Resources\MeditationResource;
use App\Services\Meditation\MeditationService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MeditationController extends Controller
{
    public function __construct(
        private readonly MeditationService $meditationService
    ) {}

    /**
     * Get active meditation categories.
     */
    public function categories(Request $request): JsonResponse
    {
        $categories = $this->meditationService->categories();

        return response()->json([
            'success' => true,
            'message' => 'Meditation categories retrieved successfully.',
            'data' => [
                'categories' => MeditationCategoryResource::collection(
                    $categories
                ),
            ],
        ]);
    }

    /**
     * Create a meditation category.
     */
    public function storeCategory(
        StoreMeditationCategoryRequest $request
    ): JsonResponse {
        $category = $this->meditationService->createCategory(
            $request->validated()
        );

        return response()->json([
            'success' => true,
            'message' => 'Meditation category created successfully.',
            'data' => [
                'category' => new MeditationCategoryResource($category),
            ],
        ], 201);
    }

    /**
     * Create a meditation.
     */
    public function store(
        StoreMeditationRequest $request
    ): JsonResponse {
        $meditation = $this->meditationService->createMeditation(
            $request->validated()
        );

        return response()->json([
            'success' => true,
            'message' => 'Meditation created successfully.',
            'data' => [
                'meditation' => new MeditationResource($meditation),
            ],
        ], 201);
    }

    /**
     * Get published meditations.
     */
    public function index(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'type' => ['sometimes', 'string', 'in:guided,free,breathing,individual'],
            'category' => ['sometimes', 'string', 'max:100'],
            'language' => ['sometimes', 'string', 'max:10'],
            'max_duration' => ['sometimes', 'integer', 'min:1'],
            'q' => ['sometimes', 'string', 'max:191'],
            'page' => ['sometimes', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ]);

        $meditations = $this->meditationService->catalog(
            $request->user(),
            $filters
        );

        return response()->json([
            'success' => true,
            'message' => 'Meditations retrieved successfully.',
            'data' => [
                'meditations' => MeditationResource::collection(
                    $meditations->items()
                ),
                'meta' => [
                    'current_page' => $meditations->currentPage(),
                    'per_page' => $meditations->perPage(),
                    'total' => $meditations->total(),
                    'last_page' => $meditations->lastPage(),
                ],
            ],
        ]);
    }

    /**
     * Get the meditation home / daily overview.
     */
    public function home(Request $request): JsonResponse
    {
        $home = $this->meditationService->home($request->user());

        return response()->json([
            'success' => true,
            'message' => 'Meditation home retrieved successfully.',
            'data' => [
                'today' => $home['today'],
                'week' => $home['week'],
                'streak' => $home['streak'],
                'shortcuts' => $home['shortcuts'],
                'short_meditations' => MeditationResource::collection(
                    $home['short_meditations']
                ),
                'reminder' => $home['reminder'] !== null
                    ? new MeditationReminderResource($home['reminder'])
                    : null,
            ],
        ]);
    }

    /**
     * Get meditation statistics.
     */
    public function stats(Request $request): JsonResponse
    {
        $stats = $this->meditationService->stats($request->user());

        return response()->json([
            'success' => true,
            'message' => 'Meditation statistics retrieved successfully.',
            'data' => $stats,
        ]);
    }

    /**
     * Get a published meditation.
     */
    public function show(Request $request, int $meditation): JsonResponse
    {
        try {
            $meditation = $this->meditationService->find(
                $meditation,
                $request->user()
            );
        } catch (ModelNotFoundException) {
            return response()->json([
                'success' => false,
                'message' => 'Meditation not found.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Meditation retrieved successfully.',
            'data' => [
                'meditation' => new MeditationResource($meditation),
            ],
        ]);
    }
}
