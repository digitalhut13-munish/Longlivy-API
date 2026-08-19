<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserStreakResource;
use App\Services\Streak\StreakService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StreakController extends Controller
{
    public function __construct(
        private readonly StreakService $streakService
    ) {
    }

    /**
     * Get all user streaks.
     */
    public function index(Request $request): JsonResponse
    {
        $streaks = $this->streakService->getUserStreaks(
            $request->user()
        );

        return response()->json([
            'success' => true,
            'message' => 'Streaks retrieved successfully.',
            'data' => [
                'streaks' => UserStreakResource::collection($streaks),
            ],
        ]);
    }

    /**
     * Get a specific streak.
     */
    public function show(
        Request $request,
        string $type
    ): JsonResponse {
        $streak = $this->streakService->get(
            $request->user(),
            $type
        );

        return response()->json([
            'success' => true,
            'message' => 'Streak retrieved successfully.',
            'data' => [
                'streak' => new UserStreakResource($streak),
            ],
        ]);
    }
}