<?php

namespace Database\Factories;

use App\Models\Food;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Food>
 */
class FoodFactory extends Factory
{
    protected $model = Food::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'name' => ucfirst($this->faker->words(2, true)),
            'brand' => $this->faker->company(),
            'category_id' => null,
            'barcode' => $this->faker->unique()->ean13(),
            'base_unit' => 'g',
            'base_amount' => 100,
            'calories' => $this->faker->numberBetween(10, 500),
            'protein' => $this->faker->numberBetween(0, 30),
            'carbohydrates' => $this->faker->numberBetween(0, 60),
            'fat' => $this->faker->numberBetween(0, 30),
            'fiber' => $this->faker->numberBetween(0, 10),
            'sugar' => $this->faker->numberBetween(0, 20),
            'saturated_fat' => $this->faker->numberBetween(0, 10),
            'sodium' => $this->faker->numberBetween(0, 1000),
            'grams_per_unit' => null,
            'ml_per_unit' => null,
            'serving_amount' => null,
            'source' => 'manual',
            'verified' => false,
            'is_custom' => false,
            'external_id' => null,
        ];
    }
}