<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MeditationSessionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'meditation' => $this->whenLoaded(
                'meditation',
                fn ($meditation) => $meditation !== null
                    ? new MeditationResource($meditation)
                    : null
            ),

            'type' => $this->type,
            'status' => $this->status,
            'is_running' => $this->isRunning(),

            'planned_minutes' => $this->planned_minutes,
            'actual_minutes' => $this->actual_minutes,

            'started_at' => $this->started_at?->toISOString(),
            'ended_at' => $this->ended_at?->toISOString(),
            'paused_at' => $this->paused_at?->toISOString(),

            'position_seconds' => $this->position_seconds,
            'active_seconds' => $this->active_seconds,
            'paused_seconds' => $this->paused_seconds,
            'progress_updated_at' => $this->progress_updated_at?->toISOString(),

            'date' => $this->date?->format('Y-m-d'),

            'elapsed_seconds' => $this->elapsedSeconds(),
            'progress_percent' => $this->progressPercent(),

            'notes' => $this->notes,

            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
