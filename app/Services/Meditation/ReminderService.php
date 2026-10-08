<?php

namespace App\Services\Meditation;

use App\Models\MeditationReminder;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

class ReminderService
{
    public function list(User $user): Collection
    {
        return $user->meditationReminders()
            ->orderBy('time')
            ->get();
    }

    public function create(User $user, array $data): MeditationReminder
    {
        return $user->meditationReminders()->create($data);
    }

    public function update(
        MeditationReminder $reminder,
        array $data
    ): MeditationReminder {
        $reminder->update($data);

        return $reminder->fresh();
    }

    public function delete(MeditationReminder $reminder): void
    {
        $reminder->delete();
    }
}
