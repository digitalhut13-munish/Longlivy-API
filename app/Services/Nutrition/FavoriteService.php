<?php

namespace App\Services\Nutrition;

use App\Models\Favorite;
use App\Models\Food;
use App\Models\Meal;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\ValidationException;

class FavoriteService
{
    /**
     * @var array<string, class-string>
     */
    private const TYPE_MAP = [
        Favorite::TYPE_FOOD => Food::class,
        Favorite::TYPE_MEAL => Meal::class,
    ];

    public function list(User $user, ?string $type = null): Collection
    {
        $query = $user->favorites()
            ->with('favoritable')
            ->orderByDesc('created_at');

        if ($type !== null) {
            $query->where('favoritable_type', $this->morphType($type));
        }

        return $query->get();
    }

    public function add(User $user, string $type, int $id): Favorite
    {
        $morphType = $this->morphType($type);

        $target = match ($type) {
            Favorite::TYPE_FOOD => $this->resolveFood($user, $id),
            Favorite::TYPE_MEAL => $this->resolveMeal($user, $id),
            default => null,
        };

        if ($target === null) {
            throw ValidationException::withMessages([
                'id' => 'The selected item is not available.',
            ]);
        }

        $existing = $user->favorites()
            ->where('favoritable_type', $morphType)
            ->where('favoritable_id', $id)
            ->first();

        if ($existing !== null) {
            return $existing;
        }

        $favorite = new Favorite([
            'user_id' => $user->id,
            'favoritable_type' => $morphType,
            'favoritable_id' => $id,
            'created_at' => now(),
        ]);

        if ($target instanceof Meal) {
            $favorite->snapshot = [
                'name' => $target->name,
                'meal_type' => $target->meal_type,
                'items' => $target->items->map(fn ($item) => [
                    'food_id' => $item->food_id,
                    'name' => $item->name,
                    'quantity' => (float) $item->quantity,
                    'unit' => $item->unit,
                    'calories' => (float) $item->calories,
                ])->values()->all(),
            ];
        }

        $favorite->save();

        return $favorite->setRelation('favoritable', $target);
    }

    public function remove(User $user, Favorite $favorite): void
    {
        if ($favorite->user_id !== $user->id) {
            abort(403);
        }

        $favorite->delete();
    }

    private function morphType(string $type): string
    {
        if (! isset(self::TYPE_MAP[$type])) {
            throw ValidationException::withMessages([
                'type' => 'The type must be one of: '
                    .implode(', ', array_keys(self::TYPE_MAP)).'.',
            ]);
        }

        return self::TYPE_MAP[$type];
    }

    private function resolveFood(User $user, int $id): ?Food
    {
        return Food::where('id', $id)
            ->where(function ($builder) use ($user) {
                $builder->where('is_custom', false)
                    ->orWhere('user_id', $user->id);
            })
            ->first();
    }

    private function resolveMeal(User $user, int $id): ?Meal
    {
        return $user->meals()->with('items')->find($id);
    }
}
