<?php

namespace App\Services\Weight;

use App\Events\WeightLogged;
use App\Models\User;
use App\Models\WeightLog;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class WeightService
{
    /**
     * @param  array<string, mixed>  $filters
     */
    public function getLogs(
        User $user,
        array $filters = []
    ): LengthAwarePaginator {
        $query = $user->weightLogs()->orderByDesc('logged_at');

        if (isset($filters['from']) && $filters['from'] !== null) {
            $query->whereDate('date', '>=', $filters['from']);
        }

        if (isset($filters['to']) && $filters['to'] !== null) {
            $query->whereDate('date', '<=', $filters['to']);
        }

        $perPage = (int) ($filters['per_page'] ?? $filters['limit'] ?? 50);

        return $query->paginate($perPage)->withQueryString();
    }

    public function latest(User $user): ?WeightLog
    {
        return $user->weightLogs()
            ->orderByDesc('logged_at')
            ->first();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function store(User $user, array $data): WeightLog
    {
        $timezone = $user->profile?->timezone() ?? 'UTC';

        $loggedAt = isset($data['logged_at'])
            ? Carbon::parse($data['logged_at'])->setTimezone($timezone)
            : Carbon::now($timezone);

        $log = new WeightLog([
            'user_id' => $user->id,
            'weight' => $data['weight'],
            'unit' => $data['unit'] ?? 'kg',
            'logged_at' => $loggedAt,
            'date' => $loggedAt->toDateString(),
            'source' => $data['source'] ?? WeightLog::SOURCE_MANUAL,
            'external_id' => $data['external_id'] ?? null,
            'notes' => $data['notes'] ?? null,
        ]);

        $log->save();

        $this->syncProfileWeight($user, $log);

        WeightLogged::dispatch($user, $log);

        return $log;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(WeightLog $log, array $data): WeightLog
    {
        $user = $log->user;

        $timezone = $user->profile?->timezone() ?? 'UTC';

        if (isset($data['logged_at'])) {
            $loggedAt = Carbon::parse($data['logged_at'])->setTimezone($timezone);

            $data['logged_at'] = $loggedAt;
            $data['date'] = $loggedAt->toDateString();
        }

        $log->fill($data);
        $log->save();

        if ($this->isLatestFor($log)) {
            $this->syncProfileWeight($user, $log);
        }

        WeightLogged::dispatch($user, $log);

        return $log;
    }

    public function delete(WeightLog $log): void
    {
        $user = $log->user;

        $wasLatest = $this->isLatestFor($log);

        $log->delete();

        if ($wasLatest) {
            $latest = $this->latest($user);

            if ($latest !== null) {
                $this->syncProfileWeight($user, $latest);
            }
        }

        WeightLogged::dispatch($user, $log);
    }

    private function isLatestFor(WeightLog $log): bool
    {
        $latest = $this->latest($log->user);

        return $latest !== null && $latest->is($log);
    }

    private function syncProfileWeight(User $user, WeightLog $log): void
    {
        $profile = $user->profile;

        if ($profile === null) {
            return;
        }

        $profile->current_weight = $log->weight;
        $profile->weight_unit = $log->unit;
        $profile->save();
    }
}
