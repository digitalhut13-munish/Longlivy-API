<?php

namespace App\Services\Notification;

use App\Contracts\Integrations\PushNotificationService;
use App\Models\Notification;
use App\Models\NotificationSetting;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class NotificationService
{
    public function __construct(
        private readonly PushNotificationService $push
    ) {
    }

    /**
     * Defaults matching the app's DEFAULT_SETTINGS: everything on
     * except the two that are opt-in by default.
     */
    public const DEFAULT_SETTINGS = [
        'fasting_begins' => true,
        'fasting_ends' => true,
        'eating_phase_begins' => true,
        'interim_goal_achieved' => true,
        'fasting_almost_over' => true,
        'planned_fast_not_started' => true,
        'fasting_streak_reached' => true,
        'personal_best_achieved' => true,
        'activity_reminder' => true,
        'activity_goal_achieved' => true,
        'calorie_goal_almost_reached' => true,
        'calorie_target_exceeded' => true,
        'protein_goal_achieved' => true,
        'weight_reminder' => false,
        'meditation_reminder' => false,
    ];

    /**
     * Deliver an in-app notification (always) plus a device push when
     * a transport is configured. Returns null when the type is unknown
     * or the user turned the event off, unless $force is set (used by
     * the test command).
     *
     * @param  array<string, mixed>  $data
     */
    public function send(
        User $user,
        string $type,
        string $title,
        string $body,
        array $data = [],
        bool $force = false
    ): ?Notification {
        if (! array_key_exists($type, self::DEFAULT_SETTINGS)) {
            return null;
        }

        if (! $force && ! ($this->settings($user)[$type] ?? false)) {
            return null;
        }

        $notification = $user->notifications()->create([
            'type' => $type,
            'title' => $title,
            'body' => $body,
            'data' => $data !== [] ? $data : null,
        ]);

        $this->push->send($user, $type, $data + [
            'notification_id' => $notification->id,
            'title' => $title,
            'body' => $body,
        ]);

        return $notification;
    }

    public function inbox(
        User $user,
        bool $unreadOnly = false,
        int $page = 1,
        int $perPage = 30
    ): LengthAwarePaginator {
        $query = $user->notifications()
            ->orderByDesc('created_at')
            ->orderByDesc('id');

        if ($unreadOnly) {
            $query->whereNull('read_at');
        }

        return $query->paginate($perPage, ['*'], 'page', $page);
    }

    public function unreadCount(User $user): int
    {
        return $user->notifications()
            ->whereNull('read_at')
            ->count();
    }

    public function markRead(User $user, string $id): Notification
    {
        $notification = $user->notifications()->findOrFail($id);
        $notification->markAsRead();

        return $notification;
    }

    public function markAllRead(User $user): int
    {
        return $user->notifications()
            ->whereNull('read_at')
            ->update(['read_at' => now()]);
    }

    public function settings(User $user): array
    {
        $rows = $user->notificationSettings()
            ->pluck('enabled', 'event');

        $settings = self::DEFAULT_SETTINGS;

        foreach ($rows as $event => $enabled) {
            $settings[$event] = (bool) $enabled;
        }

        return $settings;
    }

    public function saveSettings(User $user, array $changes): array
    {
        foreach ($changes as $event => $enabled) {
            NotificationSetting::updateOrCreate(
                [
                    'user_id' => $user->id,
                    'event' => $event,
                ],
                [
                    'enabled' => (bool) $enabled,
                ]
            );
        }

        return $this->settings($user);
    }
}