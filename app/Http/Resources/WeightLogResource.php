<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WeightLogResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'weight' => $this->weight,
            'unit' => $this->unit,
            'logged_at' => $this->logged_at?->toIso8601String(),
            'date' => $this->date?->toDateString(),
            'source' => $this->source,
            'notes' => $this->notes,
        ];
    }
}
