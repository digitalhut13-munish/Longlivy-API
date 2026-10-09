<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MeditationTemplateResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'duration_seconds' => $this->duration_seconds,
            'type' => $this->type,
            'breathing_enabled' => $this->breathing_enabled,
            'closing_sound_enabled' => $this->closing_sound_enabled,
            'background_sound' => $this->background_sound,
            'meditation_id' => $this->meditation_id,

            'meditation' => $this->whenLoaded(
                'meditation',
                fn ($meditation) => $meditation !== null
                    ? new MeditationResource($meditation)
                    : null
            ),

            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}