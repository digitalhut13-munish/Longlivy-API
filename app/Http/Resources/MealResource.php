<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MealResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $data = [
            'id' => $this->id,
            'meal_type' => $this->meal_type,
            'name' => $this->name,
            'logged_at' => $this->logged_at?->toIso8601String(),
            'date' => $this->date?->toDateString(),
            'totals' => [
                'calories' => $this->total_calories,
                'protein' => $this->total_protein,
                'carbohydrates' => $this->total_carbohydrates,
                'fat' => $this->total_fat,
                'fiber' => $this->total_fiber,
                'sodium' => $this->total_sodium,
            ],
            'notes' => $this->notes,
            'source' => $this->source,
        ];

        if ($this->relationLoaded('items')) {
            $data['items'] = MealItemResource::collection(
                $this->items
            );
        }

        return $data;
    }
}
