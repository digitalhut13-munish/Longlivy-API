<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MeditationReminderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'time' => $this->time,
            'days_of_week' => $this->days_of_week,
            'days' => array_map(
                'intval',
                explode(',', (string) $this->days_of_week)
            ),
            'label' => $this->label,
            'enabled' => $this->enabled,

            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
