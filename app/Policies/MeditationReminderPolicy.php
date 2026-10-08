<?php

namespace App\Policies;

use App\Models\MeditationReminder;
use App\Models\User;

class MeditationReminderPolicy
{
    public function view(User $user, MeditationReminder $reminder): bool
    {
        return $reminder->user_id === $user->id;
    }

    public function update(User $user, MeditationReminder $reminder): bool
    {
        return $reminder->user_id === $user->id;
    }

    public function delete(User $user, MeditationReminder $reminder): bool
    {
        return $reminder->user_id === $user->id;
    }
}
