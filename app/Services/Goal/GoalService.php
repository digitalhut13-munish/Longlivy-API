<?php

namespace App\Services\Goal;

use App\Models\Goal;
use App\Models\GoalProgress;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

class GoalService
{
    public function getUserGoals(User $user): Collection
    {
        return $user->goals()
            ->where('active', true)
            ->latest()
            ->get();
    }

    public function create(
        User $user,
        array $data
    ): Goal {
        return $user->goals()->create($data);
    }

    public function update(
        Goal $goal,
        array $data
    ): Goal {
        // A value edited by the user is a manual target; it must never
        // be silently overwritten by the next recalculation. An
        // explicit "source" in the body always wins.
        if (array_key_exists('target_value', $data)
            && ! array_key_exists('source', $data)
        ) {
            $data['source'] = 'manual';
        }

        $goal->update($data);

        return $goal->fresh();
    }

    public function delete(Goal $goal): void
    {
        $goal->delete();
    }

    public function addProgress(
        Goal $goal,
        array $data
    ): GoalProgress {
        return $goal->progress()->updateOrCreate(
            [
                'date' => $data['date'],
            ],
            [
                'value' => $data['value'],
                'completed' => $data['completed'],
            ]
        );
    }

    public function getProgress(
        Goal $goal
    ): Collection {
        return $goal->progress()
            ->orderByDesc('date')
            ->get();
    }

    public function incrementProgress(
        Goal $goal,
        string $date,
        float $delta
    ): GoalProgress {
        $existing = $goal->progress()
            ->whereDate('date', $date)
            ->first();

        $value = round(
            (float) ($existing?->value ?? 0) + $delta,
            2
        );

        return $goal->progress()->updateOrCreate(
            [
                'date' => $date,
            ],
            [
                'value' => $value,
                'completed' => $value >= (float) $goal->target_value,
            ]
        );
    }
}