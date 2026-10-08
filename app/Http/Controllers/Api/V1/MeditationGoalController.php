<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Meditation\StoreMeditationGoalProgressRequest;
use App\Http\Requests\Api\V1\Meditation\StoreMeditationGoalRequest;
use App\Http\Requests\Api\V1\Meditation\UpdateMeditationGoalRequest;
use App\Http\Resources\GoalProgressResource;
use App\Http\Resources\GoalResource;
use App\Models\Goal;
use App\Services\Meditation\MeditationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MeditationGoalController extends Controller
{
    public function __construct(
        private readonly MeditationService $meditationService
    ) {}

    /**
     * Get the user's personal meditation goals.
     */
    public function index(Request $request): JsonResponse
    {
        $goals = $this->meditationService->goals(
            $request->user()
        );

        return response()->json([
            'success' => true,
            'message' => 'Meditation goals retrieved successfully.',
            'data' => [
                'goals' => GoalResource::collection($goals),
            ],
        ]);
    }

    /**
     * Create a personal meditation goal.
     */
    public function store(
        StoreMeditationGoalRequest $request
    ): JsonResponse {
        $goal = $this->meditationService->createGoal(
            $request->user(),
            $request->validated()
        );

        return response()->json([
            'success' => true,
            'message' => 'Meditation goal created successfully.',
            'data' => [
                'goal' => new GoalResource($goal),
            ],
        ], 201);
    }

    /**
     * Get a meditation goal.
     */
    public function show(
        Request $request,
        int $goal
    ): JsonResponse {
        $goal = $this->meditationService->findGoal(
            $request->user(),
            $goal
        );

        return response()->json([
            'success' => true,
            'message' => 'Meditation goal retrieved successfully.',
            'data' => [
                'goal' => new GoalResource($goal),
            ],
        ]);
    }

    /**
     * Update a meditation goal.
     */
    public function update(
        UpdateMeditationGoalRequest $request,
        int $goal
    ): JsonResponse {
        $goal = $this->meditationService->findGoal(
            $request->user(),
            $goal
        );

        $this->authorize('update', $goal);

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

        unset($data['goal_type']);

        $goal = $this->meditationService->updateGoal(
            $goal,
            $data
        );

        return response()->json([
            'success' => true,
            'message' => 'Meditation goal updated successfully.',
            'data' => [
                'goal' => new GoalResource($goal),
            ],
        ]);
    }

    /**
     * Delete a meditation goal.
     */
    public function destroy(
        Request $request,
        int $goal
    ): JsonResponse {
        $goal = $this->meditationService->findGoal(
            $request->user(),
            $goal
        );

        $this->authorize('delete', $goal);

        $this->meditationService->deleteGoal($goal);

        return response()->json([
            'success' => true,
            'message' => 'Meditation goal deleted successfully.',
        ]);
    }

    /**
     * Get meditation goal progress.
     */
    public function progress(
        Request $request,
        int $goal
    ): JsonResponse {
        $goal = $this->meditationService->findGoal(
            $request->user(),
            $goal
        );

        $this->authorize('view', $goal);

        $progress = $this->meditationService->goalProgress($goal);

        return response()->json([
            'success' => true,
            'message' => 'Meditation goal progress retrieved successfully.',
            'data' => [
                'progress' => GoalProgressResource::collection($progress),
            ],
        ]);
    }

    /**
     * Save manual meditation goal progress.
     */
    public function storeProgress(
        StoreMeditationGoalProgressRequest $request,
        int $goal
    ): JsonResponse {
        $goal = $this->meditationService->findGoal(
            $request->user(),
            $goal
        );

        $this->authorize('progress', $goal);

        $progress = $this->meditationService->recordGoalProgress(
            $goal,
            $request->validated()
        );

        return response()->json([
            'success' => true,
            'message' => 'Meditation goal progress saved successfully.',
            'data' => [
                'progress' => new GoalProgressResource($progress),
            ],
        ], 201);
    }
}
