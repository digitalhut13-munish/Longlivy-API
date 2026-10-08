<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FoodResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'brand' => $this->brand,
            'category' => $this->category !== null
                ? [
                    'id' => $this->category->id,
                    'name' => $this->category->name,
                ]
                : null,
            'barcode' => $this->barcode,
            'base_unit' => $this->base_unit,
            'base_amount' => $this->base_amount,
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
            'grams_per_unit' => $this->grams_per_unit,
            'ml_per_unit' => $this->ml_per_unit,
            'serving_amount' => $this->serving_amount,
            'source' => $this->source,
            'verified' => $this->verified,
            'is_custom' => $this->is_custom,
            'owned' => $this->user_id !== null,
        ];
    }
}
