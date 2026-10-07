<?php

namespace App\Policies;

use App\Models\Fasting;
use App\Models\User;

class FastingPolicy
{
    public function view(User $user, Fasting $fasting): bool
    {
        return $fasting->user_id === $user->id;
    }

    public function update(User $user, Fasting $fasting): bool
    {
        return $fasting->user_id === $user->id;
    }

    public function delete(User $user, Fasting $fasting): bool
    {
        return $fasting->user_id === $user->id;
    }
}
