<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ActivityResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'client_id' => $this->client_id,
            'type' => $this->type,
            'source' => $this->source,
            'started_at' => $this->started_at?->toIso8601String(),
            'ended_at' => $this->ended_at?->toIso8601String(),
            'active_seconds' => $this->active_seconds,
            'paused_seconds' => $this->paused_seconds,
            'distance_meters' => $this->distance_meters,
            'calories_kcal' => $this->calories_kcal,
            'calculation_method' => $this->calculation_method,
            'avg_heart_rate' => $this->avg_heart_rate,
            'steps' => $this->steps,
            'route' => $this->route(),
            'notes' => $this->notes,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}