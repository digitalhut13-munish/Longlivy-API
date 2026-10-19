<?php

namespace App\Services\Fasting;

use App\Models\FastingPlan;
use App\Models\FastingPlanOverride;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;

class FastingPlanService
{
    public function getUserPlans(User $user): Collection
    {
        return $user->fastingPlans()
            ->orderByDesc('active')
            ->orderByDesc('created_at')
            ->get();
    }

    public function getPlan(User $user, int $id): FastingPlan
    {
        return $user->fastingPlans()
            ->findOrFail($id);
    }

    public function create(User $user, array $data): FastingPlan
    {
        $this->assertChecksum((int) $data['fasting_hours'], (int) $data['eating_hours']);

        $active = $data['active'] ?? true;

        if ($active) {
            $this->deactivateOthers($user, 0);
        }

        return $user->fastingPlans()
            ->create($this->prepareData($data));
    }

    public function update(
        FastingPlan $plan,
        array $data
    ): FastingPlan {
        $this->assertChecksum(
            $data['fasting_hours'] ?? $plan->fasting_hours,
            $data['eating_hours'] ?? $plan->eating_hours,
        );

        $active = $data['active'] ?? $plan->active;

        if ($active && ! $plan->active) {
            $this->deactivateOthers($plan->user, $plan->id);
        }

        $plan->update($this->prepareUpdateData($data));

        return $plan->fresh();
    }

    public function delete(FastingPlan $plan): void
    {
        $plan->delete();
    }

    public function getOverrides(
        FastingPlan $plan
    ): Collection {
        return $plan->overrides()
            ->orderByDesc('date')
            ->get();
    }

    public function saveOverride(
        FastingPlan $plan,
        string $date,
        array $data
    ): FastingPlanOverride {
        $override = $plan->overrides()->where('date', $date)->first();

        $attributes = [
            'user_id' => $plan->user_id,
            'action' => $data['action'],
            'start_time' => $data['start_time'] ?? null,
            'end_time' => $data['end_time'] ?? null,
        ];

        if ($override === null) {
            $override = $plan->overrides()->create(
                $attributes + ['date' => $date]
            );
        } else {
            $override->update($attributes);
        }

        return $override->fresh();
    }

    public function deleteOverride(
        FastingPlan $plan,
        string $date
    ): void {
        $plan->overrides()
            ->where('date', $date)
            ->delete();
    }

    private function prepareData(array $data): array
    {
        return [
            'method' => $data['method'],
            'category' => $data['category'],
            'recurring' => $data['recurring'],
            'start_time' => $data['start_time'],
            'end_time' => $data['end_time'],
            'weekdays' => $this->normalizeWeekdays($data['weekdays']),
            'start_date' => $data['start_date'],
            'end_date' => $data['end_date'] ?? null,
            'timezone' => $data['timezone'],
            'fasting_hours' => $data['fasting_hours'],
            'eating_hours' => $data['eating_hours'],
            'active' => $data['active'] ?? true,
            'notification_settings' => $data['notification_settings'] ?? [],
        ];
    }

    /**
     * Partial updates only touch the keys that were actually sent, so a
     * payload such as {"active": false} never trips over missing fields.
     */
    private function prepareUpdateData(array $data): array
    {
        $attributes = Arr::only($data, [
            'method',
            'category',
            'recurring',
            'start_time',
            'end_time',
            'weekdays',
            'start_date',
            'end_date',
            'timezone',
            'fasting_hours',
            'eating_hours',
            'active',
            'notification_settings',
        ]);

        if (array_key_exists('weekdays', $attributes)) {
            $attributes['weekdays'] = $this->normalizeWeekdays(
                $attributes['weekdays']
            );
        }

        return $attributes;
    }

    /**
     * Weekdays round-trip exactly as sent: a list of 0-6 integers
     * (0 = Sunday), with no deduplication or reordering.
     */
    private function normalizeWeekdays(mixed $weekdays): array
    {
        return array_values(array_map('intval', (array) $weekdays));
    }

    private function deactivateOthers(
        User $user,
        int $exceptId
    ): void {
        $user->fastingPlans()
            ->where('id', '!=', $exceptId)
            ->where('active', true)
            ->update(['active' => false]);
    }

    private function assertChecksum(int $fasting, int $eating): void
    {
        $wantedFasting = (int) $fasting;
        $wantedEating = (int) $eating;

        // Users may pick any hours, even mismatched method strings,
        // as long as they stay inside the supported range.
        if ($wantedFasting < 1 || $wantedFasting > 96) {
            throw ValidationException::withMessages([
                'fasting_hours' => 'Fasting hours must be between 1 and 96.',
            ]);
        }

        if ($wantedEating < 0 || $wantedEating > 23) {
            throw ValidationException::withMessages([
                'eating_hours' => 'Eating hours must be between 0 and 23.',
            ]);
        }

        if ($wantedFasting + $wantedEating > 168) {
            throw ValidationException::withMessages([
                'fasting_hours' => 'Fasting and eating hours cannot exceed a week.',
            ]);
        }
    }
}