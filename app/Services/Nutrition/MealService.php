<?php

namespace App\Services\Nutrition;

use App\Events\NutritionDayChanged;
use App\Models\Food;
use App\Models\Meal;
use App\Models\MealItem;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\ValidationException;

class MealService
{
    public function __construct(
        private readonly FoodService $foodService,
        private readonly NutritionCalculator $calculator
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     */
    public function getMeals(User $user, array $filters = []): Collection
    {
        $query = $user->meals()->with('items')->orderByDesc('logged_at');

        if (! empty($filters['date'])) {
            $query->whereDate('date', $filters['date']);
        }

        if (! empty($filters['from'])) {
            $query->whereDate('date', '>=', $filters['from']);
        }

        if (! empty($filters['to'])) {
            $query->whereDate('date', '<=', $filters['to']);
        }

        if (! empty($filters['meal_type'])) {
            $query->where('meal_type', $filters['meal_type']);
        }

        return $query->get();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(User $user, array $data): Meal
    {
        $timezone = $user->profile?->timezone() ?? 'UTC';

        $loggedAt = isset($data['logged_at'])
            ? Carbon::parse($data['logged_at'])->setTimezone($timezone)
            : Carbon::now($timezone);

        $meal = new Meal([
            'user_id' => $user->id,
            'meal_type' => $data['meal_type'],
            'name' => $data['name'] ?? null,
            'logged_at' => $loggedAt,
            'date' => $loggedAt->toDateString(),
            'notes' => $data['notes'] ?? null,
            'source' => $data['source'] ?? 'manual',
        ]);

        $meal->save();

        foreach ($data['items'] ?? [] as $itemData) {
            $this->addItem($meal, $itemData);
        }

        $this->notify($user, [$meal->date->toDateString()]);

        return $meal->fresh(['items']);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Meal $meal, array $data): Meal
    {
        $previousDate = $meal->date->toDateString();

        if (isset($data['logged_at'])) {
            $timezone = $meal->user->profile?->timezone() ?? 'UTC';

            $loggedAt = Carbon::parse($data['logged_at'])
                ->setTimezone($timezone);

            $data['logged_at'] = $loggedAt;
            $data['date'] = $loggedAt->toDateString();
        }

        $meal->fill($data);
        $meal->save();

        $this->notify(
            $meal->user,
            [$previousDate, $meal->date->toDateString()]
        );

        return $meal->fresh(['items']);
    }

    public function delete(Meal $meal): void
    {
        $date = $meal->date->toDateString();
        $user = $meal->user;

        $meal->delete();

        $this->notify($user, [$date]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function addItem(Meal $meal, array $data): MealItem
    {
        $item = new MealItem([
            'meal_id' => $meal->id,
            'name' => $data['name'] ?? null,
            'quantity' => $data['quantity'],
            'unit' => $data['unit'] ?? 'g',
            'position' => $meal->items()->count(),
        ]);

        if (! empty($data['food_id'])) {
            $this->attachFromFood($item, $meal, $data);
        } else {
            $this->attachManual($item, $data);
        }

        $item->save();

        $this->recomputeTotals($meal);

        $this->notify($meal->user, [$meal->date->toDateString()]);

        return $item;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateItem(
        Meal $meal,
        MealItem $item,
        array $data
    ): MealItem {
        if (isset($data['food_id'])) {
            $fresh = new MealItem(['meal_id' => $meal->id]);
            $this->attachFromFood($fresh, $meal, $data);
            $item->fill($fresh->getAttributes());
        } elseif (isset($data['quantity'])) {
            if ($item->food_id !== null) {
                $food = $this->foodService->findForUser(
                    $meal->user,
                    $item->food_id
                );

                if ($food !== null) {
                    $snapshot = $this->calculator->compute(
                        $food,
                        (float) $data['quantity'],
                        $data['unit'] ?? $item->unit
                    );

                    $item->quantity = $data['quantity'];
                    $item->unit = $data['unit'] ?? $item->unit;
                    $item->fill($snapshot);
                }
            } else {
                $item->quantity = $data['quantity'];
                $item->unit = $data['unit'] ?? $item->unit;
                $this->applyManualValues($item, $data, true);
            }
        }

        if (isset($data['name'])) {
            $item->name = $data['name'];
        }

        $item->save();

        $this->recomputeTotals($meal);

        $this->notify($meal->user, [$meal->date->toDateString()]);

        return $item;
    }

    public function deleteItem(Meal $meal, MealItem $item): void
    {
        $date = $meal->date->toDateString();
        $user = $meal->user;

        $item->delete();

        $this->recomputeTotals($meal);

        $this->notify($user, [$date]);
    }

    /**
     * Quick action: append one food to the meal matching
     * (day, meal type), creating the meal when needed.
     *
     * @param  array<string, mixed>  $data
     */
    public function log(User $user, array $data): Meal
    {
        $timezone = $user->profile?->timezone() ?? 'UTC';

        $loggedAt = isset($data['logged_at'])
            ? Carbon::parse($data['logged_at'])->setTimezone($timezone)
            : Carbon::now($timezone);

        $date = $loggedAt->toDateString();

        $meal = $user->meals()
            ->whereDate('date', $date)
            ->where('meal_type', $data['meal_type'])
            ->orderBy('id')
            ->first();

        if ($meal === null) {
            $meal = new Meal([
                'user_id' => $user->id,
                'meal_type' => $data['meal_type'],
                'logged_at' => $loggedAt,
                'date' => $date,
                'source' => 'manual',
            ]);

            $meal->save();
        }

        $this->addItem($meal, [
            'food_id' => $data['food_id'] ?? null,
            'name' => $data['name'] ?? null,
            'quantity' => $data['quantity'],
            'unit' => $data['unit'] ?? 'g',
            'calories' => $data['calories'] ?? null,
            'protein' => $data['protein'] ?? null,
            'carbohydrates' => $data['carbohydrates'] ?? null,
            'fat' => $data['fat'] ?? null,
            'fiber' => $data['fiber'] ?? null,
        ]);

        return $meal->fresh(['items']);
    }

    public function recomputeTotals(Meal $meal): void
    {
        $items = $meal->items;

        $meal->total_calories = $items->sum('calories');
        $meal->total_protein = $items->sum('protein');
        $meal->total_carbohydrates = $items->sum('carbohydrates');
        $meal->total_fat = $items->sum('fat');
        $meal->total_fiber = $items->sum('fiber');
        $meal->total_sodium = $items->sum('sodium');

        $meal->save();
    }

    /**
     * Signal that the nutrition data of the given days changed so
     * projections (energy balance, statistics) refresh themselves.
     *
     * @param  array<int, string>  $dates
     */
    private function notify(User $user, array $dates): void
    {
        NutritionDayChanged::dispatch($user, array_values($dates));
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function attachFromFood(
        MealItem $item,
        Meal $meal,
        array $data
    ): void {
        $food = $this->foodService->findForUser(
            $meal->user,
            (int) $data['food_id']
        );

        if ($food === null) {
            throw ValidationException::withMessages([
                'food_id' => 'The selected food is not available.',
            ]);
        }

        $snapshot = $this->calculator->compute(
            $food,
            (float) $data['quantity'],
            $data['unit'] ?? 'g'
        );

        $item->food_id = $food->id;
        $item->name = $data['name'] ?? $food->name;
        $item->brand = $food->brand;
        $item->quantity = $data['quantity'];
        $item->unit = $data['unit'] ?? 'g';
        $item->fill($snapshot);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function attachManual(
        MealItem $item,
        array $data
    ): void {
        if (empty($data['name'])) {
            throw ValidationException::withMessages([
                'food_id' => 'Either a food_id or a name is required.',
            ]);
        }

        if (! isset($data['calories'])) {
            throw ValidationException::withMessages([
                'calories' => 'The calories field is required for '
                    .'entries that are not linked to a food.',
            ]);
        }

        $item->food_id = null;
        $item->name = $data['name'];
        $item->brand = $data['brand'] ?? null;
        $item->quantity = $data['quantity'];
        $item->unit = $data['unit'] ?? 'g';

        $this->applyManualValues($item, $data, false);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function applyManualValues(
        MealItem $item,
        array $data,
        bool $onlyProvided
    ): void {
        $defaults = [
            'calories' => 0,
            'protein' => 0,
            'carbohydrates' => 0,
            'fat' => 0,
            'fiber' => null,
            'sugar' => null,
            'saturated_fat' => null,
            'sodium' => null,
        ];

        foreach ($defaults as $field => $fallback) {
            if (array_key_exists($field, $data) && $data[$field] !== null) {
                $item->{$field} = $data[$field];
            } elseif (! $onlyProvided) {
                $item->{$field} = $fallback;
            }
        }
    }
}
