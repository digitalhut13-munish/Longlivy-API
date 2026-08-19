<?php

namespace App\Services\Streak;

use App\Models\User;
use App\Models\UserStreak;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;

class StreakService
{
    public function getUserStreaks(User $user): Collection
    {
        return $user->streaks()
            ->orderBy('type')
            ->get();
    }

    public function get(
        User $user,
        string $type
    ): UserStreak {
        return $user->streaks()->firstOrCreate(
            [
                'type' => $type,
            ],
            [
                'current_streak' => 0,
                'longest_streak' => 0,
            ]
        );
    }

    public function markCompleted(
        User $user,
        string $type,
        Carbon $date
    ): UserStreak {
        $streak = $this->get($user, $type);

        $lastCompletedDate = $streak->last_completed_date;

        if ($lastCompletedDate === null) {
            $streak->current_streak = 1;
            $streak->current_streak_started_at = $date;
        } elseif ($lastCompletedDate->isSameDay($date)) {
            return $streak;
        } elseif ($lastCompletedDate->copy()->addDay()->isSameDay($date)) {
            $streak->current_streak++;
        } else {
            $streak->current_streak = 1;
            $streak->current_streak_started_at = $date;
        }

        $streak->last_completed_date = $date;

        if ($streak->current_streak > $streak->longest_streak) {
            $streak->longest_streak = $streak->current_streak;
        }

        $streak->save();

        return $streak->fresh();
    }
}