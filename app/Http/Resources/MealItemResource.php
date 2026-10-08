<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MealItemResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'food_id' => $this->food_id,
            'name' => $this->name,
            'brand' => $this->brand,
            'quantity' => $this->quantity,
            'unit' => $this->unit,
            'nutrients' => [
                'calories' => $this->calories,
                'protein' => $this->protein,
                'carbohydrates' => $this->carbohydrates,
                'fat' => $this->fat,
                'fiber' => $this->fiber,
                'sugar' => $this->sugar,
                'saturated_fat' => $this->saturated_fat,
                'sodium' => $this->sodium,
            ],
            'position' => $this->position,
        ];
    }
}
