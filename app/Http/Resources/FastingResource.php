<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FastingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'fasting_type' => $this->fasting_type,
            'planned_hours' => $this->planned_hours,

            'status' => $this->status,
            'is_active' => $this->isOngoing(),

            'started_at' => $this->started_at?->toISOString(),
            'ended_at' => $this->ended_at?->toISOString(),
            'date' => $this->date?->format('Y-m-d'),

            'actual_hours' => $this->actual_hours,
            'elapsed_hours' => $this->elapsedHours(),
            'progress_percent' => $this->progressPercent(),

            'notes' => $this->notes,

            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
