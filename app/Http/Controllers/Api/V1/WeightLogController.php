<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Weight\StoreWeightLogRequest;
use App\Http\Requests\Api\V1\Weight\UpdateWeightLogRequest;
use App\Http\Resources\WeightLogResource;
use App\Models\WeightLog;
use App\Services\Weight\WeightService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WeightLogController extends Controller
{
    public function __construct(
        private readonly WeightService $weightService
    ) {
    }

    /**
     * List weight entries.
     */
    public function index(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:365'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $logs = $this->weightService->getLogs(
            $request->user(),
            $filters
        );

        return response()->json([
            'success' => true,
            'message' => 'Weight entries retrieved successfully.',
            'data' => [
                'weight_logs' => WeightLogResource::collection(
                    $logs->items()
                ),
                'meta' => [
                    'current_page' => $logs->currentPage(),
                    'per_page' => $logs->perPage(),
                    'total' => $logs->total(),
                    'last_page' => $logs->lastPage(),
                ],
            ],
        ]);
    }

    /**
     * Log a weight entry.
     */
    public function store(
        StoreWeightLogRequest $request
    ): JsonResponse {
        $log = $this->weightService->store(
            $request->user(),
            $request->validated()
        );

        return response()->json([
            'success' => true,
            'message' => 'Weight entry saved successfully.',
            'data' => [
                'weight_log' => new WeightLogResource($log),
            ],
        ], 201);
    }

    /**
     * Get the most recent weight entry.
     */
    public function latest(Request $request): JsonResponse
    {
        $log = $this->weightService->latest($request->user());

        return response()->json([
            'success' => true,
            'message' => 'Latest weight entry retrieved successfully.',
            'data' => [
                'weight_log' => $log !== null
                    ? new WeightLogResource($log)
                    : null,
            ],
        ]);
    }

    /**
     * Get a weight entry.
     */
    public function show(
        Request $request,
        WeightLog $weightLog
    ): JsonResponse {
        $this->authorize('view', $weightLog);

        return response()->json([
            'success' => true,
            'message' => 'Weight entry retrieved successfully.',
            'data' => [
                'weight_log' => new WeightLogResource($weightLog),
            ],
        ]);
    }

    /**
     * Update a weight entry.
     */
    public function update(
        UpdateWeightLogRequest $request,
        WeightLog $weightLog
    ): JsonResponse {
        $this->authorize('update', $weightLog);

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

        $log = $this->weightService->update($weightLog, $data);

        return response()->json([
            'success' => true,
            'message' => 'Weight entry updated successfully.',
            'data' => [
                'weight_log' => new WeightLogResource($log),
            ],
        ]);
    }

    /**
     * Delete a weight entry.
     */
    public function destroy(
        Request $request,
        WeightLog $weightLog
    ): JsonResponse {
        $this->authorize('delete', $weightLog);

        $this->weightService->delete($weightLog);

        return response()->json([
            'success' => true,
            'message' => 'Weight entry deleted successfully.',
        ]);
    }
}
