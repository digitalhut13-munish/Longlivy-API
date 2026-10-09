<?php

namespace App\Services\Activity;

use App\Events\ActivityChanged;
use App\Models\Activity;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class ActivityService
{
    /**
     * Save a finished activity. Idempotent by client_id: an existing
     * client_id returns HTTP 200 with the stored record instead of
     * creating a duplicate.
     */
    public function save(User $user, array $data): array
    {
        $existing = $user->activities()
            ->where('client_id', $data['client_id'])
            ->first();

        $route = $data['route'] ?? null;

        $attributes = [
            'user_id' => $user->id,
            'client_id' => $data['client_id'],
            'type' => $data['type'],
            'source' => $data['source'],
            'started_at' => Carbon::parse($data['started_at']),
            'ended_at' => Carbon::parse($data['ended_at']),
            'active_seconds' => $data['active_seconds'],
            'paused_seconds' => $data['paused_seconds'],
            'distance_meters' => $data['distance_meters'] ?? null,
            'calories_kcal' => $data['calories_kcal'],
            'calculation_method' => $data['calculation_method'],
            'avg_heart_rate' => $data['avg_heart_rate'] ?? null,
            'steps' => $data['steps'] ?? null,
            'route_polyline' => $route['points'] ?? null,
            'route_point_count' => $route['point_count'] ?? null,
            'notes' => $data['notes'] ?? null,
        ];

        if ($existing !== null) {
            $existing->update($attributes);
            $activity = $existing->fresh();
            $created = false;
        } else {
            $activity = $user->activities()->create($attributes);
            $created = true;
        }

        ActivityChanged::dispatch($user, $activity);

        return [$activity, $created];
    }

    public function update(Activity $activity, array $data): Activity
    {
        $activity->update($data);
        $activity = $activity->fresh();

        ActivityChanged::dispatch($activity->user, $activity);

        return $activity;
    }

    public function delete(Activity $activity): void
    {
        $user = $activity->user;

        ActivityChanged::dispatch($user, $activity, true);
        $activity->delete();
    }

    public function list(
        User $user,
        array $filters,
        int $page = 1,
        int $perPage = 50
    ): LengthAwarePaginator {
        $query = $user->activities()
            ->orderByDesc('started_at');

        if (! empty($filters['from'])) {
            $query->whereDate('started_at', '>=', $filters['from']);
        }

        if (! empty($filters['to'])) {
            $query->whereDate('started_at', '<=', $filters['to']);
        }

        if (! empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        return $query->paginate($perPage, ['*'], 'page', $page);
    }
}