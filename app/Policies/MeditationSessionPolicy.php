<?php

namespace App\Policies;

use App\Models\MeditationSession;
use App\Models\User;

class MeditationSessionPolicy
{
    public function view(User $user, MeditationSession $session): bool
    {
        return $session->user_id === $user->id;
    }

    public function update(User $user, MeditationSession $session): bool
    {
        return $session->user_id === $user->id;
    }

    public function delete(User $user, MeditationSession $session): bool
    {
        return $session->user_id === $user->id;
    }
}
