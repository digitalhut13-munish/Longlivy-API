<?php

namespace App\Http\Resources;

use App\Models\Meditation;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MeditationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'type' => $this->type,

            'category' => $this->whenLoaded(
                'category',
                fn ($category) => $category !== null
                    ? new MeditationCategoryResource($category)
                    : null
            ),

            'sound_category' => $this->sound_category,
            'language' => $this->language,

            'duration_minutes' => $this->duration_minutes,
            'duration_seconds' => $this->durationSeconds(),

            'thumbnail_url' => $this->thumbnail_path,
            'audio' => $this->audioObject($request),

            'availability' => $this->availability,
            'is_premium' => $this->is_premium,
            'is_favorite' => $this->favorites->isNotEmpty(),

            'breathing_pattern' => $this->type === Meditation::TYPE_BREATHING
                ? $this->breathingPattern()
                : null,

            'status' => $this->status,
            'version' => $this->version,
            'released_at' => $this->released_at?->format('Y-m-d'),

            // Kept for backward compatibility with earlier clients.
            'audio_url' => $this->audio_url,
            'background_audio_url' => $this->background_audio_url,
            'background_type' => $this->background_type,

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

    private function audioObject(Request $request): ?array
    {
        $language = $request->input('language', $this->language);

        $track = $this->audio
            ->firstWhere('language', $language)
            ?? $this->audio->firstWhere('language', $this->language)
            ?? $this->audio->first();

        if ($track !== null) {
            return [
                'url' => $track->url(),
                'format' => $track->format,
                'mime_type' => $track->mime_type,
                'bitrate_kbps' => $track->bitrate_kbps,
                'duration_seconds' => $track->duration_seconds
                    ?? $this->durationSeconds(),
                'size_bytes' => $track->size_bytes,
                'is_streamable' => $track->isStreamable(),
                'expires_at' => null,
            ];
        }

        if ($this->audio_url === null) {
            return null;
        }

        $format = strtolower(
            (string) pathinfo(
                (string) parse_url($this->audio_url, PHP_URL_PATH),
                PATHINFO_EXTENSION
            )
        ) ?: 'mp3';

        return [
            'url' => $this->audio_url,
            'format' => $format,
            'mime_type' => $this->mimeTypeFor($format),
            'bitrate_kbps' => null,
            'duration_seconds' => $this->durationSeconds(),
            'size_bytes' => null,
            'is_streamable' => true,
            'expires_at' => null,
        ];
    }

    private function mimeTypeFor(string $format): string
    {
        return match ($format) {
            'm4a' => 'audio/mp4',
            'aac' => 'audio/aac',
            'wav' => 'audio/wav',
            default => 'audio/mpeg',
        };
    }
}