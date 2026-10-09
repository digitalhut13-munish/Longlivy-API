<?php

namespace App\Services\Meditation;

use App\Models\Meditation;
use App\Models\MeditationSession;
use App\Models\User;
use App\Services\Streak\StreakService;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Validation\ValidationException;

class SessionService
{
    public function __construct(
        private readonly StreakService $streakService
    ) {}

    public function getUserSessions(
        User $user,
        array $filters = []
    ): LengthAwarePaginator {
        $query = $user->meditationSessions()
            ->with('meditation')
            ->orderByDesc('started_at');

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        if (! empty($filters['from'])) {
            $query->whereDate('date', '>=', $filters['from']);
        }

        if (! empty($filters['to'])) {
            $query->whereDate('date', '<=', $filters['to']);
        }

        $perPage = (int) ($filters['per_page'] ?? 50);

        return $query->paginate($perPage)->withQueryString();
    }

    public function getActive(User $user): ?MeditationSession
    {
        return $user->meditationSessions()
            ->whereIn('status', [
                MeditationSession::STATUS_ACTIVE,
                MeditationSession::STATUS_PAUSED,
            ])
            ->with('meditation')
            ->latest('started_at')
            ->first();
    }

    public function start(User $user, array $data): MeditationSession
    {
        if ($this->getActive($user) !== null) {
            throw ValidationException::withMessages([
                'session' => 'A meditation session is already in progress.',
            ]);
        }

        $meditation = null;

        if (! empty($data['meditation_id'])) {
            $meditation = Meditation::find($data['meditation_id']);

            if ($meditation === null
                || ! $meditation->isPublished()) {
                throw ValidationException::withMessages([
                    'meditation_id' => 'The selected meditation is not available.',
                ]);
            }
        }

        $type = $data['type'] ?? $meditation?->type;

        if ($type === null) {
            throw ValidationException::withMessages([
                'type' => 'Meditation type is required.',
            ]);
        }

        $startedAt = ! empty($data['started_at'])
            ? Carbon::parse($data['started_at'])
            : now();

        return $user->meditationSessions()->create([
            'meditation_id' => $meditation?->id,
            'type' => $type,
            'status' => MeditationSession::STATUS_ACTIVE,
            'planned_minutes' => $data['planned_minutes'],
            'started_at' => $startedAt,
            'date' => $startedAt->toDateString(),
            'notes' => $data['notes'] ?? null,
        ]);
    }

    public function pause(MeditationSession $session): MeditationSession
    {
        if (! $session->isActive()) {
            throw ValidationException::withMessages([
                'session' => 'This meditation session is not active.',
            ]);
        }

        $session->status = MeditationSession::STATUS_PAUSED;
        $session->paused_at = now();
        $session->save();

        return $session->fresh();
    }

    public function resume(MeditationSession $session): MeditationSession
    {
        if (! $session->isPaused()) {
            throw ValidationException::withMessages([
                'session' => 'This meditation session is not paused.',
            ]);
        }

        $session->paused_seconds += $session->paused_at !== null
            ? $session->paused_at->diffInSeconds(now())
            : 0;
        $session->paused_at = null;
        $session->status = MeditationSession::STATUS_ACTIVE;
        $session->save();

        return $session->fresh();
    }

    public function complete(
        MeditationSession $session,
        ?Carbon $endedAt = null,
        ?int $clientActiveSeconds = null,
        ?int $clientPausedSeconds = null
    ): MeditationSession {
        if (! $session->isRunning()) {
            throw ValidationException::withMessages([
                'session' => 'This meditation session is not running.',
            ]);
        }

        $endedAt = $endedAt ?? now();

        if ($endedAt->lessThan($session->started_at)) {
            throw ValidationException::withMessages([
                'ended_at' => 'End time cannot be before the start time.',
            ]);
        }

        $elapsed = $session->started_at->diffInSeconds($endedAt);

        $usesClient = $clientActiveSeconds !== null
            || $clientPausedSeconds !== null;

        if ($clientActiveSeconds !== null) {
            if ($clientActiveSeconds < 0
                || $clientActiveSeconds > $elapsed) {
                throw ValidationException::withMessages([
                    'active_seconds' => 'Active time cannot exceed the '
                        .'elapsed session time.',
                ]);
            }

            $activeSeconds = $clientActiveSeconds;
            $pausedSeconds = $clientPausedSeconds ?? 0;
        } elseif ($clientPausedSeconds !== null) {
            $pausedSeconds = max(0, $clientPausedSeconds);
            $activeSeconds = max(0, $elapsed - $pausedSeconds);
        } else {
            $pausedSeconds = $session->paused_seconds;

            if ($session->isPaused()
                && $session->paused_at !== null
                && $session->paused_at->lessThan($endedAt)) {
                $pausedSeconds += $session->paused_at->diffInSeconds($endedAt);
            }

            $activeSeconds = max(0, $elapsed - $pausedSeconds);
        }

        $session->status = MeditationSession::STATUS_COMPLETED;
        $session->ended_at = $endedAt;
        $session->paused_at = null;
        $session->paused_seconds = $pausedSeconds;
        $session->active_seconds = $activeSeconds;
        $session->actual_minutes = $usesClient
            ? intdiv($activeSeconds, 60)
            : (int) ceil($activeSeconds / 60);
        $session->save();

        $this->syncStreak($session);
        $this->syncGoals($session);

        return $session->fresh();
    }

    /**
     * Store playback progress reported by the client. Updates that
     * arrive with an older client_timestamp than the last stored one
     * are ignored so out-of-order heartbeats cannot rewind a session.
     */
    public function saveProgress(
        MeditationSession $session,
        array $data
    ): MeditationSession {
        if (! $session->isRunning()) {
            throw ValidationException::withMessages([
                'session' => 'This meditation session is not running.',
            ]);
        }

        $clientTimestamp = Carbon::parse($data['client_timestamp']);

        if ($session->progress_updated_at !== null
            && $clientTimestamp->lessThanOrEqualTo(
                $session->progress_updated_at
            )) {
            return $session;
        }

        $session->position_seconds = (int) $data['position_seconds'];
        $session->active_seconds = (int) $data['active_seconds'];
        $session->paused_seconds = (int) $data['paused_seconds'];
        $session->progress_updated_at = $clientTimestamp;
        $session->save();

        return $session->fresh();
    }

    public function cancel(MeditationSession $session): MeditationSession
    {
        if (! $session->isRunning()) {
            throw ValidationException::withMessages([
                'session' => 'This meditation session is not running.',
            ]);
        }

        $session->status = MeditationSession::STATUS_CANCELLED;
        $session->ended_at = now();
        $session->paused_at = null;
        $session->save();

        return $session->fresh();
    }

    public function update(
        MeditationSession $session,
        array $data
    ): MeditationSession {
        $session->update($data);

        return $session->fresh();
    }

    public function delete(MeditationSession $session): void
    {
        $session->delete();
    }

    private function syncStreak(MeditationSession $session): void
    {
        $dayMinutes = (int) $session->user->meditationSessions()
            ->where('status', MeditationSession::STATUS_COMPLETED)
            ->whereDate('date', $session->date)
            ->sum('actual_minutes');

        $threshold = (int) config(
            'longlivy.meditation.min_streak_minutes'
        );

        if ($dayMinutes < $threshold) {
            return;
        }

        $this->streakService->markCompleted(
            $session->user,
            'meditation',
            $session->date->copy()->startOfDay()
        );
    }

    private function syncGoals(MeditationSession $session): void
    {
        $minutes = (int) $session->actual_minutes;

        $goals = $session->user->goals()
            ->where('goal_type', 'meditation')
            ->where('active', true)
            ->get();

        foreach ($goals as $goal) {
            $tracksMinutes = preg_match(
                '/min/i',
                (string) $goal->unit
            ) === 1;

            $delta = $tracksMinutes
                ? $minutes
                : ($minutes > 0 ? 1 : 0);

            if ($delta <= 0) {
                continue;
            }

            $existing = $goal->progress()
                ->whereDate('date', $session->date)
                ->first();

            $value = round(
                (float) ($existing?->value ?? 0) + $delta,
                2
            );

            $goal->progress()->updateOrCreate(
                [
                    'date' => $session->date,
                ],
                [
                    'value' => $value,
                    'completed' => $value
                        >= (float) $goal->target_value,
                ]
            );
        }
    }
}
