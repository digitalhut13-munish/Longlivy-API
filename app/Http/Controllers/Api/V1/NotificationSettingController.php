<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Notification\UpdateNotificationSettingsRequest;
use App\Services\Notification\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationSettingController extends Controller
{
    public function __construct(
        private readonly NotificationService $notificationService
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'Notification settings retrieved successfully.',
            'data' => [
                'settings' => $this->notificationService->settings(
                    $request->user()
                ),
            ],
        ]);
    }

    public function update(
        UpdateNotificationSettingsRequest $request
    ): JsonResponse {
        $settings = $this->notificationService->saveSettings(
            $request->user(),
            $request->validated()['settings']
        );

        return response()->json([
            'success' => true,
            'message' => 'Notification settings updated successfully.',
            'data' => [
                'settings' => $settings,
            ],
        ]);
    }
}