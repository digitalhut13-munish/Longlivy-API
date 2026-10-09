<?php

namespace App\Policies;

use App\Models\FastingPlan;
use App\Models\User;

class FastingPlanPolicy
{
    public function view(User $user, FastingPlan $plan): bool
    {
        return $plan->user_id === $user->id;
    }

    public function update(User $user, FastingPlan $plan): bool
    {
        return $plan->user_id === $user->id;
    }

    public function delete(User $user, FastingPlan $plan): bool
    {
        return $plan->user_id === $user->id;
    }
}