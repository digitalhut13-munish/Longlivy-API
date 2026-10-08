<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Meditation\StoreMeditationReminderRequest;
use App\Http\Requests\Api\V1\Meditation\UpdateMeditationReminderRequest;
use App\Http\Resources\MeditationReminderResource;
use App\Models\MeditationReminder;
use App\Services\Meditation\ReminderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MeditationReminderController extends Controller
{
    public function __construct(
        private readonly ReminderService $reminderService
    ) {}

    /**
     * Get the user's meditation reminders.
     */
    public function index(Request $request): JsonResponse
    {
        $reminders = $this->reminderService->list(
            $request->user()
        );

        return response()->json([
            'success' => true,
            'message' => 'Meditation reminders retrieved successfully.',
            'data' => [
                'reminders' => MeditationReminderResource::collection(
                    $reminders
                ),
            ],
        ]);
    }

    /**
     * Create a meditation reminder.
     */
    public function store(
        StoreMeditationReminderRequest $request
    ): JsonResponse {
        $reminder = $this->reminderService->create(
            $request->user(),
            $request->validated()
        );

        return response()->json([
            'success' => true,
            'message' => 'Meditation reminder created successfully.',
            'data' => [
                'reminder' => new MeditationReminderResource($reminder),
            ],
        ], 201);
    }

    /**
     * Update a meditation reminder.
     */
    public function update(
        UpdateMeditationReminderRequest $request,
        MeditationReminder $reminder
    ): JsonResponse {
        $this->authorize('update', $reminder);

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

        $reminder = $this->reminderService->update(
            $reminder,
            $data
        );

        return response()->json([
            'success' => true,
            'message' => 'Meditation reminder updated successfully.',
            'data' => [
                'reminder' => new MeditationReminderResource($reminder),
            ],
        ]);
    }

    /**
     * Delete a meditation reminder.
     */
    public function destroy(
        MeditationReminder $reminder
    ): JsonResponse {
        $this->authorize('delete', $reminder);

        $this->reminderService->delete($reminder);

        return response()->json([
            'success' => true,
            'message' => 'Meditation reminder deleted successfully.',
        ]);
    }
}
