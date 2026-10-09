<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\NotificationResource;
use App\Models\Notification;
use App\Services\Notification\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function __construct(
        private readonly NotificationService $notificationService
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        $data = $request->validate([
            'unread_only' => ['nullable', 'boolean'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $user = $request->user();

        $page = (int) ($data['page'] ?? 1);
        $perPage = (int) ($data['per_page'] ?? 30);

        $inbox = $this->notificationService->inbox(
            $user,
            filter_var($data['unread_only'] ?? false, FILTER_VALIDATE_BOOL),
            $page,
            $perPage
        );

        return response()->json([
            'success' => true,
            'message' => 'Notifications retrieved successfully.',
            'data' => [
                'notifications' => NotificationResource::collection(
                    $inbox->items()
                ),
                'unread_count' => $this->notificationService->unreadCount(
                    $user
                ),
                'meta' => [
                    'current_page' => $inbox->currentPage(),
                    'per_page' => $inbox->perPage(),
                    'total' => $inbox->total(),
                    'last_page' => $inbox->lastPage(),
                ],
            ],
        ]);
    }

    public function read(Request $request, Notification $notification): JsonResponse
    {
        $this->authorize('update', $notification);

        $this->notificationService->markRead(
            $request->user(),
            $notification->id
        );

        return response()->json([
            'success' => true,
            'message' => 'Marked as read.',
        ]);
    }

    public function readAll(Request $request): JsonResponse
    {
        $updated = $this->notificationService->markAllRead(
            $request->user()
        );

        return response()->json([
            'success' => true,
            'message' => 'All marked as read.',
            'data' => [
                'updated' => $updated,
            ],
        ]);
    }
}