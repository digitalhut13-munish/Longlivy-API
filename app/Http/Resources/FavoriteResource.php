<?php

namespace App\Http\Resources;

use App\Models\Favorite;
use App\Models\Food;
use App\Models\Meal;
use App\Models\Meditation;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FavoriteResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $target = $this->resource->favoritable;

        return [
            'id' => $this->id,
            'type' => $this->type(),
            'favoritable_id' => $this->favoritable_id,
            'created_at' => $this->created_at?->toIso8601String(),
            'available' => $target !== null,
            'snapshot' => $this->snapshot,
            'target' => $this->serializeTarget($target),
        ];
    }

    private function type(): string
    {
        return match ($this->favoritable_type) {
            Food::class => Favorite::TYPE_FOOD,
            Meal::class => Favorite::TYPE_MEAL,
            Meditation::class => Favorite::TYPE_MEDITATION,
            default => class_basename($this->favoritable_type),
        };
    }

    private function serializeTarget(mixed $target): array|string|null
    {
        if ($target === null) {
            return null;
        }

        if ($target instanceof Food) {
            return (new FoodResource($target))->toArray($this->parentRequest());
        }

        if ($target instanceof Meal) {
            return (new MealResource($target))->toArray($this->parentRequest());
        }

        if ($target instanceof Meditation) {
            return $target->only([
                'id',
                'type',
                'title',
                'duration_minutes',
                'status',
            ]);
        }

        return $target->only(['id', 'title', 'status']);
    }

    private function parentRequest(): Request
    {
        return request();
    }
}
