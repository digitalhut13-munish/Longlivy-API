<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Fasting\EndFastingRequest;
use App\Http\Requests\Api\V1\Fasting\StoreFastingRequest;
use App\Http\Requests\Api\V1\Fasting\UpdateFastingRequest;
use App\Http\Resources\FastingResource;
use App\Models\Fasting;
use App\Services\Fasting\FastingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FastingController extends Controller
{
    public function __construct(
        private readonly FastingService $fastingService
    ) {
    }

    /**
     * Get user's fasting sessions.
     */
    public function index(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'status' => [
                'sometimes',
                'string',
                'in:ongoing,completed,failed',
            ],
            'from' => ['sometimes', 'date'],
            'to' => ['sometimes', 'date'],
        ]);

        $fastings = $this->fastingService->getUserFastings(
            $request->user(),
            $filters
        );

        return response()->json([
            'success' => true,
            'message' => 'Fasting sessions retrieved successfully.',
            'data' => [
                'fastings' => FastingResource::collection($fastings),
            ],
        ]);
    }

    /**
     * Get the currently running fasting session.
     */
    public function active(Request $request): JsonResponse
    {
        $fasting = $this->fastingService->getActive(
            $request->user()
        );

        return response()->json([
            'success' => true,
            'message' => 'Active fasting session retrieved successfully.',
            'data' => [
                'fasting' => $fasting
                    ? new FastingResource($fasting)
                    : null,
            ],
        ]);
    }

    /**
     * Get fasting statistics.
     */
    public function summary(Request $request): JsonResponse
    {
        $summary = $this->fastingService->summary(
            $request->user()
        );

        return response()->json([
            'success' => true,
            'message' => 'Fasting summary retrieved successfully.',
            'data' => $summary,
        ]);
    }

    /**
     * Start a fasting session.
     */
    public function store(
        StoreFastingRequest $request
    ): JsonResponse {
        $fasting = $this->fastingService->start(
            $request->user(),
            $request->validated()
        );

        return response()->json([
            'success' => true,
            'message' => 'Fasting session started successfully.',
            'data' => [
                'fasting' => new FastingResource($fasting),
            ],
        ], 201);
    }

    /**
     * Get a fasting session.
     */
    public function show(
        Request $request,
        Fasting $fasting
    ): JsonResponse {
        $this->authorize('view', $fasting);

        return response()->json([
            'success' => true,
            'message' => 'Fasting session retrieved successfully.',
            'data' => [
                'fasting' => new FastingResource($fasting),
            ],
        ]);
    }

    /**
     * Update a fasting session.
     */
    public function update(
        UpdateFastingRequest $request,
        Fasting $fasting
    ): JsonResponse {
        $this->authorize('update', $fasting);

        $data = $request->validated();

        if ($data === []) {
            return response()->json([
                'success' => false,
                'message' => 'No updatable fields received.',
                'errors' => [
                    'body' => [
                        'Send the payload as JSON with '
                        . 'Content-Type: application/json. Note that '
                        . 'multipart/form-data is not supported for PUT.',
                    ],
                ],
            ], 422);
        }

        $fasting = $this->fastingService->update(
            $fasting,
            $data
        );

        return response()->json([
            'success' => true,
            'message' => 'Fasting session updated successfully.',
            'data' => [
                'fasting' => new FastingResource($fasting),
            ],
        ]);
    }

    /**
     * End a fasting session.
     */
    public function end(
        EndFastingRequest $request,
        Fasting $fasting
    ): JsonResponse {
        $this->authorize('update', $fasting);

        $fasting = $this->fastingService->end(
            $fasting,
            ! empty($request->validated()['ended_at'])
                ? \Carbon\Carbon::parse(
                    $request->validated()['ended_at']
                )
                : null
        );

        return response()->json([
            'success' => true,
            'message' => 'Fasting session ended successfully.',
            'data' => [
                'fasting' => new FastingResource($fasting),
            ],
        ]);
    }

    /**
     * Delete a fasting session.
     */
    public function destroy(
        Request $request,
        Fasting $fasting
    ): JsonResponse {
        $this->authorize('delete', $fasting);

        $this->fastingService->delete($fasting);

        return response()->json([
            'success' => true,
            'message' => 'Fasting session deleted successfully.',
        ]);
    }
}
