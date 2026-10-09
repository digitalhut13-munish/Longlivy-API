<?php

namespace App\Policies;

use App\Models\MeditationTemplate;
use App\Models\User;

class MeditationTemplatePolicy
{
    public function view(User $user, MeditationTemplate $template): bool
    {
        return $template->user_id === $user->id;
    }

    public function update(User $user, MeditationTemplate $template): bool
    {
        return $template->user_id === $user->id;
    }

    public function delete(User $user, MeditationTemplate $template): bool
    {
        return $template->user_id === $user->id;
    }
}