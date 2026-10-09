<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\FastingPlan\StoreFastingPlanOverrideRequest;
use App\Http\Requests\Api\V1\FastingPlan\StoreFastingPlanRequest;
use App\Http\Requests\Api\V1\FastingPlan\UpdateFastingPlanRequest;
use App\Http\Resources\FastingPlanOverrideResource;
use App\Http\Resources\FastingPlanResource;
use App\Models\FastingPlan;
use App\Services\Fasting\FastingPlanService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FastingPlanController extends Controller
{
    public function __construct(
        private readonly FastingPlanService $fastingPlanService
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        $plans = $this->fastingPlanService->getUserPlans(
            $request->user()
        );

        $plans->load('overrides');

        return response()->json([
            'success' => true,
            'message' => 'Fasting plans retrieved successfully.',
            'data' => [
                'plans' => FastingPlanResource::collection($plans),
            ],
        ]);
    }

    public function store(StoreFastingPlanRequest $request): JsonResponse
    {
        $plan = $this->fastingPlanService->create(
            $request->user(),
            $request->validated()
        );

        return response()->json([
            'success' => true,
            'message' => 'Fasting plan saved.',
            'data' => [
                'plan' => new FastingPlanResource($plan),
            ],
        ], 201);
    }

    public function show(Request $request, FastingPlan $plan): JsonResponse
    {
        $this->authorize('view', $plan);

        return response()->json([
            'success' => true,
            'message' => 'Fasting plan retrieved successfully.',
            'data' => [
                'plan' => new FastingPlanResource($plan->load('overrides')),
            ],
        ]);
    }

    public function update(
        UpdateFastingPlanRequest $request,
        FastingPlan $plan
    ): JsonResponse {
        $this->authorize('update', $plan);

        $plan = $this->fastingPlanService->update(
            $plan,
            $request->validated()
        );

        return response()->json([
            'success' => true,
            'message' => 'Fasting plan saved.',
            'data' => [
                'plan' => new FastingPlanResource($plan),
            ],
        ]);
    }

    public function destroy(
        Request $request,
        FastingPlan $plan
    ): JsonResponse {
        $this->authorize('delete', $plan);

        $this->fastingPlanService->delete($plan);

        return response()->json([
            'success' => true,
            'message' => 'Fasting plan deleted.',
        ]);
    }

    public function overrides(
        Request $request,
        FastingPlan $plan
    ): JsonResponse {
        $this->authorize('view', $plan);

        $overrides = $this->fastingPlanService->getOverrides($plan);

        return response()->json([
            'success' => true,
            'message' => 'Fasting plan overrides retrieved successfully.',
            'data' => [
                'overrides' => FastingPlanOverrideResource::collection(
                    $overrides
                ),
            ],
        ]);
    }

    public function saveOverride(
        StoreFastingPlanOverrideRequest $request,
        FastingPlan $plan,
        string $date
    ): JsonResponse {
        $this->authorize('update', $plan);

        $override = $this->fastingPlanService->saveOverride(
            $plan,
            $date,
            $request->validated()
        );

        return response()->json([
            'success' => true,
            'message' => 'Override saved.',
            'data' => [
                'override' => new FastingPlanOverrideResource($override),
            ],
        ]);
    }

    public function deleteOverride(
        Request $request,
        FastingPlan $plan,
        string $date
    ): JsonResponse {
        $this->authorize('update', $plan);

        $this->fastingPlanService->deleteOverride($plan, $date);

        return response()->json([
            'success' => true,
            'message' => 'Override removed.',
        ]);
    }
}