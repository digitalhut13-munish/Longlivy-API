<?php

namespace App\Policies;

use App\Models\Meal;
use App\Models\User;

class MealPolicy
{
    public function view(User $user, Meal $meal): bool
    {
        return $user->id === $meal->user_id;
    }

    public function update(User $user, Meal $meal): bool
    {
        return $user->id === $meal->user_id;
    }

    public function delete(User $user, Meal $meal): bool
    {
        return $user->id === $meal->user_id;
    }
}
