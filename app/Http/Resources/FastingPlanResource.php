<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FastingPlanResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,

            'method' => $this->method,
            'category' => $this->category,
            'recurring' => $this->recurring,

            'start_time' => $this->start_time,
            'end_time' => $this->end_time,
            'weekdays' => $this->weekdays,

            'start_date' => $this->start_date?->format('Y-m-d'),
            'end_date' => $this->end_date?->format('Y-m-d'),

            'timezone' => $this->timezone,
            'fasting_hours' => $this->fasting_hours,
            'eating_hours' => $this->eating_hours,

            'active' => $this->active,
            'notification_settings' => $this->notification_settings ?? [],

            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),

            'overrides_count' => $this->whenLoaded(
                'overrides',
                fn () => $this->overrides->count()
            ),
        ];
    }
}