<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Goal\StoreGoalProgressRequest;
use App\Http\Requests\Api\V1\Goal\StoreGoalRequest;
use App\Http\Requests\Api\V1\Goal\UpdateGoalRequest;
use App\Http\Resources\GoalProgressResource;
use App\Http\Resources\GoalResource;
use App\Services\Goal\GoalService;
use App\Models\Goal;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GoalController extends Controller
{
    public function __construct(
        private readonly GoalService $goalService
    ) {
    }

    /**
     * Get user's active goals.
     */
    public function index(Request $request): JsonResponse
    {
        $goals = $this->goalService->getUserGoals(
            $request->user()
        );

        return response()->json([
            'success' => true,
            'message' => 'Goals retrieved successfully.',
            'data' => [
                'goals' => GoalResource::collection($goals),
            ],
        ]);
    }

    /**
     * Create a goal.
     */
    public function store(
        StoreGoalRequest $request
    ): JsonResponse {
        $goal = $this->goalService->create(
            $request->user(),
            $request->validated()
        );

        return response()->json([
            'success' => true,
            'message' => 'Goal created successfully.',
            'data' => [
                'goal' => new GoalResource($goal),
            ],
        ], 201);
    }

    /**
     * Get a goal.
     */
    public function show(
        Request $request,
        Goal $goal
    ): JsonResponse {
        $this->authorize('view', $goal);

        return response()->json([
            'success' => true,
            'message' => 'Goal retrieved successfully.',
            'data' => [
                'goal' => new GoalResource($goal),
            ],
        ]);
    }

    /**
     * Update a goal.
     */
    public function update(
        UpdateGoalRequest $request,
        Goal $goal
    ): JsonResponse {
        $this->authorize('update', $goal);

        $goal = $this->goalService->update(
            $goal,
            $request->validated()
        );

        return response()->json([
            'success' => true,
            'message' => 'Goal updated successfully.',
            'data' => [
                'goal' => new GoalResource($goal),
            ],
        ]);
    }

    /**
     * Delete a goal.
     */
    public function destroy(
        Request $request,
        Goal $goal
    ): JsonResponse {
        $this->authorize('delete', $goal);

        $this->goalService->delete($goal);

        return response()->json([
            'success' => true,
            'message' => 'Goal deleted successfully.',
        ]);
    }

    /**
     * Add/update goal progress.
     */
    public function storeProgress(
        StoreGoalProgressRequest $request,
        Goal $goal
    ): JsonResponse {
        $this->authorize('progress', $goal);

        $progress = $this->goalService->addProgress(
            $goal,
            $request->validated()
        );

        return response()->json([
            'success' => true,
            'message' => 'Goal progress saved successfully.',
            'data' => [
                'progress' => new GoalProgressResource($progress),
            ],
        ]);
    }

    /**
     * Get goal progress.
     */
    public function progress(
        Request $request,
        Goal $goal
    ): JsonResponse {
        $this->authorize('view', $goal);

        $progress = $this->goalService->getProgress($goal);

        return response()->json([
            'success' => true,
            'message' => 'Goal progress retrieved successfully.',
            'data' => [
                'progress' => GoalProgressResource::collection(
                    $progress
                ),
            ],
        ]);
    }
}