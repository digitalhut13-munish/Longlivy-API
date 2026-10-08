<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\MeditationCategoryResource;
use App\Http\Resources\MeditationReminderResource;
use App\Http\Resources\MeditationResource;
use App\Services\Meditation\MeditationService;
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
        ]);

        $meditations = $this->meditationService->catalog($filters);

        return response()->json([
            'success' => true,
            'message' => 'Meditations retrieved successfully.',
            'data' => [
                'meditations' => MeditationResource::collection(
                    $meditations
                ),
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
    public function show(int $meditation): JsonResponse
    {
        $meditation = $this->meditationService->find($meditation);

        return response()->json([
            'success' => true,
            'message' => 'Meditation retrieved successfully.',
            'data' => [
                'meditation' => new MeditationResource($meditation),
            ],
        ]);
    }
}
