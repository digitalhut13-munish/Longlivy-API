<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Activity\StoreActivityRequest;
use App\Http\Requests\Api\V1\Activity\UpdateActivityRequest;
use App\Http\Resources\ActivityResource;
use App\Models\Activity;
use App\Services\Activity\ActivityService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ActivityController extends Controller
{
    public function __construct(
        private readonly ActivityService $activityService
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        $data = $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date'],
            'type' => [
                'nullable',
                'string',
                'in:running,walking,cycling,hiking,jogging,other',
            ],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $activities = $this->activityService->list(
            $request->user(),
            $data,
            (int) ($data['page'] ?? 1),
            (int) ($data['per_page'] ?? 50)
        );

        return response()->json([
            'success' => true,
            'message' => 'Activities retrieved successfully.',
            'data' => [
                'activities' => ActivityResource::collection(
                    $activities->items()
                ),
                'meta' => [
                    'current_page' => $activities->currentPage(),
                    'per_page' => $activities->perPage(),
                    'total' => $activities->total(),
                    'last_page' => $activities->lastPage(),
                ],
            ],
        ]);
    }

    public function store(StoreActivityRequest $request): JsonResponse
    {
        [$activity, $created] = $this->activityService->save(
            $request->user(),
            $request->validated()
        );

        return response()->json([
            'success' => true,
            'message' => 'Activity saved.',
            'data' => [
                'activity' => new ActivityResource($activity),
            ],
        ], $created ? 201 : 200);
    }

    public function show(Request $request, Activity $activity): JsonResponse
    {
        $this->authorize('view', $activity);

        return response()->json([
            'success' => true,
            'message' => 'Activity retrieved successfully.',
            'data' => [
                'activity' => new ActivityResource($activity),
            ],
        ]);
    }

    public function update(
        UpdateActivityRequest $request,
        Activity $activity
    ): JsonResponse {
        $this->authorize('update', $activity);

        $activity = $this->activityService->update(
            $activity,
            $request->validated()
        );

        return response()->json([
            'success' => true,
            'message' => 'Activity updated successfully.',
            'data' => [
                'activity' => new ActivityResource($activity),
            ],
        ]);
    }

    public function destroy(
        Request $request,
        Activity $activity
    ): JsonResponse {
        $this->authorize('delete', $activity);

        $this->activityService->delete($activity);

        return response()->json([
            'success' => true,
            'message' => 'Activity deleted.',
        ]);
    }
}