<?php

namespace App\Policies;

use App\Models\User;
use App\Models\WeightLog;

class WeightLogPolicy
{
    public function view(User $user, WeightLog $weightLog): bool
    {
        return $user->id === $weightLog->user_id;
    }

    public function update(User $user, WeightLog $weightLog): bool
    {
        return $user->id === $weightLog->user_id;
    }

    public function delete(User $user, WeightLog $weightLog): bool
    {
        return $user->id === $weightLog->user_id;
    }
}
