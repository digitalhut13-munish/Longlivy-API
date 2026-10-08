<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RecipeResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $servings = max(1, (int) $this->servings);

        return [
            'id' => $this->id,
            'name' => $this->name,
            'notes' => $this->notes,
            'servings' => $this->servings,
            'totals' => [
                'calories' => $this->total_calories,
                'protein' => $this->total_protein,
                'carbohydrates' => $this->total_carbohydrates,
                'fat' => $this->total_fat,
                'fiber' => $this->total_fiber,
            ],
            'per_serving' => [
                'calories' => round((float) $this->total_calories / $servings, 2),
                'protein' => round((float) $this->total_protein / $servings, 2),
                'carbohydrates' => round(
                    (float) $this->total_carbohydrates / $servings,
                    2
                ),
                'fat' => round((float) $this->total_fat / $servings, 2),
                'fiber' => round((float) $this->total_fiber / $servings, 2),
            ],
            'items' => $this->relationLoaded('items')
                ? MealItemResource::collection($this->items)
                : null,
        ];
    }
}
