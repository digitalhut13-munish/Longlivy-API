<?php

namespace App\Services\Fasting;

use App\Models\Fasting;
use App\Models\User;
use App\Services\Streak\StreakService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\ValidationException;

class FastingService
{
    public function __construct(
        private readonly StreakService $streakService
    ) {
    }

    public function getUserFastings(
        User $user,
        array $filters = []
    ): Collection {
        $query = $user->fastings()
            ->orderByDesc('started_at');

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['from'])) {
            $query->whereDate('date', '>=', $filters['from']);
        }

        if (! empty($filters['to'])) {
            $query->whereDate('date', '<=', $filters['to']);
        }

        return $query->get();
    }

    public function getActive(User $user): ?Fasting
    {
        return $user->fastings()
            ->where('status', Fasting::STATUS_ONGOING)
            ->latest('started_at')
            ->first();
    }

    public function start(
        User $user,
        array $data
    ): Fasting {
        if ($this->getActive($user) !== null) {
            throw ValidationException::withMessages([
                'fasting' =>
                    'A fasting session is already in progress.',
            ]);
        }

        $startedAt = ! empty($data['started_at'])
            ? Carbon::parse($data['started_at'])
            : now();

        return $user->fastings()->create([
            'fasting_type' => $data['fasting_type'],
            'planned_hours' => $data['planned_hours'],
            'started_at' => $startedAt,
            'status' => Fasting::STATUS_ONGOING,
            'date' => $startedAt->toDateString(),
            'notes' => $data['notes'] ?? null,
        ]);
    }

    public function end(
        Fasting $fasting,
        ?Carbon $endedAt = null
    ): Fasting {
        if (! $fasting->isOngoing()) {
            throw ValidationException::withMessages([
                'fasting' =>
                    'This fasting session has already ended.',
            ]);
        }

        $endedAt = $endedAt ?? now();

        if ($endedAt->lessThan($fasting->started_at)) {
            throw ValidationException::withMessages([
                'ended_at' =>
                    'End time cannot be before the start time.',
            ]);
        }

        $actualHours = round(
            $fasting->started_at
                ->diffInSeconds($endedAt) / 3600,
            2
        );

        $fasting->ended_at = $endedAt;
        $fasting->actual_hours = $actualHours;
        $fasting->status = $actualHours >= $fasting->planned_hours
            ? Fasting::STATUS_COMPLETED
            : Fasting::STATUS_FAILED;
        $fasting->save();

        if ($fasting->status === Fasting::STATUS_COMPLETED) {
            $this->streakService->markCompleted(
                $fasting->user,
                'fasting',
                $endedAt->copy()->startOfDay()
            );
        }

        return $fasting->fresh();
    }

    public function update(
        Fasting $fasting,
        array $data
    ): Fasting {
        $fasting->update($data);

        return $fasting->fresh();
    }

    public function delete(Fasting $fasting): void
    {
        $fasting->delete();
    }

    public function summary(User $user): array
    {
        $fastings = $user->fastings();

        $completed = (clone $fastings)
            ->where('status', Fasting::STATUS_COMPLETED);

        $failed = (clone $fastings)
            ->where('status', Fasting::STATUS_FAILED);

        $ongoing = (clone $fastings)
            ->where('status', Fasting::STATUS_ONGOING);

        $completedCount = $completed->count();
        $totalHours = round((float) $completed->sum('actual_hours'), 2);
        $longest = $fastings->max('actual_hours');

        return [
            'total' => $fastings->count(),
            'completed' => $completedCount,
            'failed' => $failed->count(),
            'ongoing' => $ongoing->count(),

            'total_hours' => $totalHours,

            'average_hours' => $completedCount > 0
                ? round($totalHours / $completedCount, 2)
                : 0,

            'longest_fast_hours' => $longest !== null
                ? round((float) $longest, 2)
                : 0,

            'streak' => $this->streakService->get(
                $user,
                'fasting'
            ),
        ];
    }
}
