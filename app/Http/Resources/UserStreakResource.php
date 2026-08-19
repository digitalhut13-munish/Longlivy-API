<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserStreakResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,

            'current_streak' => $this->current_streak,
            'longest_streak' => $this->longest_streak,

            'current_streak_started_at' =>
                $this->current_streak_started_at?->format('Y-m-d'),

            'last_completed_date' =>
                $this->last_completed_date?->format('Y-m-d'),

            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}