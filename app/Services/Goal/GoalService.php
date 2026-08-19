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
}