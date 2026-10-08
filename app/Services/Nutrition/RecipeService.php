<?php

namespace App\Services\Nutrition;

use App\Models\Recipe;
use App\Models\RecipeItem;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\ValidationException;

class RecipeService
{
    public function __construct(
        private readonly FoodService $foodService,
        private readonly NutritionCalculator $calculator
    ) {}

    public function getUserRecipes(User $user): Collection
    {
        return $user->recipes()
            ->with('items')
            ->orderByDesc('created_at')
            ->get();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(User $user, array $data): Recipe
    {
        $recipe = new Recipe([
            'user_id' => $user->id,
            'name' => $data['name'],
            'notes' => $data['notes'] ?? null,
            'servings' => $data['servings'] ?? 1,
        ]);

        $recipe->save();

        $this->syncItems($recipe, $data['items'] ?? []);

        return $recipe->fresh(['items']);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Recipe $recipe, array $data): Recipe
    {
        $recipe->fill([
            'name' => $data['name'] ?? $recipe->name,
            'notes' => $data['notes'] ?? $recipe->notes,
            'servings' => $data['servings'] ?? $recipe->servings,
        ]);

        $recipe->save();

        if (array_key_exists('items', $data)) {
            $recipe->items()->delete();
            $this->syncItems($recipe, $data['items']);
        }

        return $recipe->fresh(['items']);
    }

    public function delete(Recipe $recipe): void
    {
        $recipe->delete();
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     */
    private function syncItems(Recipe $recipe, array $items): void
    {
        foreach ($items as $position => $itemData) {
            $item = new RecipeItem([
                'recipe_id' => $recipe->id,
                'name' => $itemData['name'] ?? null,
                'quantity' => $itemData['quantity'],
                'unit' => $itemData['unit'] ?? 'g',
                'position' => $position,
            ]);

            if (! empty($itemData['food_id'])) {
                $this->applyFood($recipe, $item, $itemData);
            } else {
                $this->applyManual($item, $itemData);
            }

            $item->save();
        }

        $this->recompute($recipe);
    }

    /**
     * @param  array<string, mixed>  $itemData
     */
    private function applyFood(
        Recipe $recipe,
        RecipeItem $item,
        array $itemData
    ): void {
        $food = $this->foodService->findForUser(
            $recipe->user,
            (int) $itemData['food_id']
        );

        if ($food === null) {
            throw ValidationException::withMessages([
                'items' => 'One of the referenced foods is not available.',
            ]);
        }

        $snapshot = $this->calculator->compute(
            $food,
            (float) $itemData['quantity'],
            $itemData['unit'] ?? 'g'
        );

        $item->food_id = $food->id;
        $item->name = $itemData['name'] ?? $food->name;
        $item->fiber = $snapshot['fiber'];
        $item->calories = $snapshot['calories'];
        $item->protein = $snapshot['protein'];
        $item->carbohydrates = $snapshot['carbohydrates'];
        $item->fat = $snapshot['fat'];
    }

    /**
     * @param  array<string, mixed>  $itemData
     */
    private function applyManual(
        RecipeItem $item,
        array $itemData
    ): void {
        if (empty($itemData['name'])) {
            throw ValidationException::withMessages([
                'items' => 'Either a food_id or a name is required '
                    .'for every recipe item.',
            ]);
        }

        $item->food_id = null;
        $item->name = $itemData['name'];
        $item->calories = $itemData['calories'] ?? 0;
        $item->protein = $itemData['protein'] ?? 0;
        $item->carbohydrates = $itemData['carbohydrates'] ?? 0;
        $item->fat = $itemData['fat'] ?? 0;
        $item->fiber = $itemData['fiber'] ?? null;
    }

    private function recompute(Recipe $recipe): void
    {
        $items = $recipe->items;

        $recipe->total_calories = $items->sum('calories');
        $recipe->total_protein = $items->sum('protein');
        $recipe->total_carbohydrates = $items->sum('carbohydrates');
        $recipe->total_fat = $items->sum('fat');
        $recipe->total_fiber = $items->sum('fiber');

        $recipe->save();
    }
}
