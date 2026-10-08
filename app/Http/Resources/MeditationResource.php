<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MeditationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'title' => $this->title,
            'description' => $this->description,

            'category' => $this->whenLoaded(
                'category',
                fn ($category) => $category !== null
                    ? new MeditationCategoryResource($category)
                    : null
            ),

            'duration_minutes' => $this->duration_minutes,

            'audio_url' => $this->audio_url,
            'background_audio_url' => $this->background_audio_url,
            'background_type' => $this->background_type,

            'language' => $this->language,
            'status' => $this->status,

            'released_at' => $this->released_at?->format('Y-m-d'),
            'version' => $this->version,

            'license' => [
                'source' => $this->source,
                'rights_holder' => $this->rights_holder,
                'license_type' => $this->license_type,
                'license_url' => $this->license_url,
                'license_status' => $this->license_status,
                'attribution_required' => $this->attribution_required,
                'commercial_use_allowed' => $this->commercial_use_allowed,
            ],

            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
