<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Meditation\CompleteMeditationSessionRequest;
use App\Http\Requests\Api\V1\Meditation\StartMeditationSessionRequest;
use App\Http\Requests\Api\V1\Meditation\UpdateMeditationSessionRequest;
use App\Http\Resources\MeditationSessionResource;
use App\Models\MeditationSession;
use App\Services\Meditation\SessionService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MeditationSessionController extends Controller
{
    public function __construct(
        private readonly SessionService $sessionService
    ) {}

    /**
     * Get the user's meditation session history.
     */
    public function index(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'status' => [
                'sometimes',
                'string',
                'in:active,paused,completed,cancelled',
            ],
            'type' => [
                'sometimes',
                'string',
                'in:guided,free,breathing,individual',
            ],
            'from' => ['sometimes', 'date'],
            'to' => ['sometimes', 'date'],
        ]);

        $sessions = $this->sessionService->getUserSessions(
            $request->user(),
            $filters
        );

        return response()->json([
            'success' => true,
            'message' => 'Meditation sessions retrieved successfully.',
            'data' => [
                'sessions' => MeditationSessionResource::collection(
                    $sessions
                ),
            ],
        ]);
    }

    /**
     * Get the currently running meditation session.
     */
    public function active(Request $request): JsonResponse
    {
        $session = $this->sessionService->getActive(
            $request->user()
        );

        return response()->json([
            'success' => true,
            'message' => 'Active meditation session retrieved successfully.',
            'data' => [
                'session' => $session !== null
                    ? new MeditationSessionResource(
                        $session->load('meditation')
                    )
                    : null,
            ],
        ]);
    }

    /**
     * Start a meditation session.
     */
    public function store(
        StartMeditationSessionRequest $request
    ): JsonResponse {
        $session = $this->sessionService->start(
            $request->user(),
            $request->validated()
        );

        return response()->json([
            'success' => true,
            'message' => 'Meditation session started successfully.',
            'data' => [
                'session' => new MeditationSessionResource(
                    $session->load('meditation')
                ),
            ],
        ], 201);
    }

    /**
     * Get a meditation session.
     */
    public function show(
        Request $request,
        MeditationSession $session
    ): JsonResponse {
        $this->authorize('view', $session);

        return response()->json([
            'success' => true,
            'message' => 'Meditation session retrieved successfully.',
            'data' => [
                'session' => new MeditationSessionResource(
                    $session->load('meditation')
                ),
            ],
        ]);
    }

    /**
     * Update a meditation session.
     */
    public function update(
        UpdateMeditationSessionRequest $request,
        MeditationSession $session
    ): JsonResponse {
        $this->authorize('update', $session);

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

        $session = $this->sessionService->update($session, $data);

        return response()->json([
            'success' => true,
            'message' => 'Meditation session updated successfully.',
            'data' => [
                'session' => new MeditationSessionResource(
                    $session->load('meditation')
                ),
            ],
        ]);
    }

    /**
     * Pause a meditation session.
     */
    public function pause(
        MeditationSession $session
    ): JsonResponse {
        $this->authorize('update', $session);

        $session = $this->sessionService->pause($session);

        return response()->json([
            'success' => true,
            'message' => 'Meditation session paused successfully.',
            'data' => [
                'session' => new MeditationSessionResource($session),
            ],
        ]);
    }

    /**
     * Resume a meditation session.
     */
    public function resume(
        MeditationSession $session
    ): JsonResponse {
        $this->authorize('update', $session);

        $session = $this->sessionService->resume($session);

        return response()->json([
            'success' => true,
            'message' => 'Meditation session resumed successfully.',
            'data' => [
                'session' => new MeditationSessionResource($session),
            ],
        ]);
    }

    /**
     * Complete a meditation session and save the meditated time.
     */
    public function complete(
        CompleteMeditationSessionRequest $request,
        MeditationSession $session
    ): JsonResponse {
        $this->authorize('update', $session);

        $data = $request->validated();

        $session = $this->sessionService->complete(
            $session,
            ! empty($data['ended_at'])
                ? Carbon::parse($data['ended_at'])
                : null
        );

        return response()->json([
            'success' => true,
            'message' => 'Meditation session completed successfully.',
            'data' => [
                'session' => new MeditationSessionResource(
                    $session->load('meditation')
                ),
            ],
        ]);
    }

    /**
     * Cancel a meditation session without counting it.
     */
    public function cancel(
        MeditationSession $session
    ): JsonResponse {
        $this->authorize('update', $session);

        $session = $this->sessionService->cancel($session);

        return response()->json([
            'success' => true,
            'message' => 'Meditation session cancelled successfully.',
            'data' => [
                'session' => new MeditationSessionResource($session),
            ],
        ]);
    }

    /**
     * Delete a meditation session.
     */
    public function destroy(
        MeditationSession $session
    ): JsonResponse {
        $this->authorize('delete', $session);

        $this->sessionService->delete($session);

        return response()->json([
            'success' => true,
            'message' => 'Meditation session deleted successfully.',
        ]);
    }
}
