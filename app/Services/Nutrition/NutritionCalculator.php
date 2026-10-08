<?php

namespace App\Services\Nutrition;

use App\Models\Food;
use Illuminate\Validation\ValidationException;

class NutritionCalculator
{
    /**
     * Nutrients captured per meal item / recipe item.
     *
     * @var array<int, string>
     */
    public const NUTRIENTS = [
        'calories',
        'protein',
        'carbohydrates',
        'fat',
        'fiber',
        'sugar',
        'saturated_fat',
        'sodium',
    ];

    /**
     * Convert a logged quantity into the food's base units.
     *
     * @throws ValidationException when the unit does not fit the food.
     */
    public function toBaseUnits(
        Food $food,
        float $quantity,
        string $unit
    ): float {
        $base = $food->base_unit;

        $converted = match ($unit) {
            'g' => $this->expect($base, 'g', $quantity, 'g'),
            'kg' => $this->expect($base, 'g', $quantity * 1000, 'g'),
            'ml' => $this->expect($base, 'ml', $quantity, 'ml'),
            'l' => $this->expect($base, 'ml', $quantity * 1000, 'ml'),
            'piece' => $this->fromPiece($food, $quantity),
            'serving' => $this->fromServing($food, $quantity),
            'custom' => $quantity,
            default => throw ValidationException::withMessages([
                'unit' => 'The unit must be one of: '
                    .implode(', ', Food::units()).'.',
            ]),
        };

        if ($converted <= 0) {
            throw ValidationException::withMessages([
                'quantity' => 'The quantity must be greater than zero '
                    .'once converted to the food base unit.',
            ]);
        }

        return $converted;
    }

    /**
     * Nutrient snapshot for a logged quantity.
     *
     * @return array<string, float|null>
     */
    public function compute(
        Food $food,
        float $quantity,
        string $unit
    ): array {
        $baseQuantity = $this->toBaseUnits($food, $quantity, $unit);

        $factor = $baseQuantity / (float) $food->base_amount;

        $snapshot = [];

        foreach (self::NUTRIENTS as $nutrient) {
            $value = $food->{$nutrient};

            $snapshot[$nutrient] = $value === null
                ? null
                : round((float) $value * $factor, 2);
        }

        return $snapshot;
    }

    private function expect(
        string $foodBase,
        string $expected,
        float $quantity,
        string $label
    ): float {
        if ($foodBase !== $expected) {
            throw ValidationException::withMessages([
                'unit' => "This food is measured in {$foodBase}, "
                    ."so the quantity must be sent in {$label} "
                    .'or a compatible unit.',
            ]);
        }

        return $quantity;
    }

    private function fromPiece(Food $food, float $quantity): float
    {
        if ($food->base_unit === 'g' && $food->grams_per_unit !== null) {
            return $quantity * (float) $food->grams_per_unit;
        }

        if ($food->base_unit === 'ml' && $food->ml_per_unit !== null) {
            return $quantity * (float) $food->ml_per_unit;
        }

        throw ValidationException::withMessages([
            'unit' => 'This food does not define a piece weight, '
                .'so the quantity cannot be sent in piece.',
        ]);
    }

    private function fromServing(Food $food, float $quantity): float
    {
        if ($food->serving_amount === null) {
            throw ValidationException::withMessages([
                'unit' => 'This food does not define a serving size, '
                    .'so the quantity cannot be sent in serving.',
            ]);
        }

        return $quantity * (float) $food->serving_amount;
    }
}
