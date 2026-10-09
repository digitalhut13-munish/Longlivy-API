<?php

namespace App\Services\Notification;

use App\Models\Notification;
use App\Models\NotificationSetting;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class NotificationService
{
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