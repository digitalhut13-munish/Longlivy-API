<?php

namespace App\Policies;

use App\Models\Food;
use App\Models\User;

class FoodPolicy
{
    public function view(User $user, Food $food): bool
    {
        if (! $food->is_custom) {
            return true;
        }

        return $food->isOwnedBy($user);
    }

    public function update(User $user, Food $food): bool
    {
        return $food->is_custom && $food->isOwnedBy($user);
    }

    public function delete(User $user, Food $food): bool
    {
        return $this->update($user, $food);
    }
}
