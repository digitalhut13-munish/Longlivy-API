<?php

namespace App\Services\Meditation;

use App\Models\MeditationTemplate;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

class MeditationTemplateService
{
    public function getUserTemplates(User $user): Collection
    {
        return $user->meditationTemplates()
            ->with('meditation')
            ->orderByDesc('created_at')
            ->get();
    }

    public function create(User $user, array $data): MeditationTemplate
    {
        $template = $user->meditationTemplates()->create([
            'name' => $data['name'],
            'duration_seconds' => $data['duration_seconds'],
            'type' => $data['type'],
            'breathing_enabled' => $data['breathing_enabled'] ?? false,
            'closing_sound_enabled' => $data['closing_sound_enabled'] ?? true,
            'background_sound' => $data['background_sound'] ?? null,
            'meditation_id' => $data['meditation_id'] ?? null,
        ]);

        return $template->load('meditation');
    }

    public function update(
        MeditationTemplate $template,
        array $data
    ): MeditationTemplate {
        $template->update($data);

        return $template->fresh()->load('meditation');
    }

    public function delete(MeditationTemplate $template): void
    {
        $template->delete();
    }
}