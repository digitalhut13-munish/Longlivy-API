<?php

namespace Database\Seeders;

use App\Models\Food;
use App\Models\FoodCategory;
use Illuminate\Database\Seeder;

/**
 * Shared food catalog (user_id NULL = catalog row).
 *
 * Nutrient values are per 100 g/ml base_amount and licensed-so-far
 * reference data modelled on USDA FoodData Central entries. The set is
 * deliberately small: enough for the search, barcode and recognition
 * flows to work end to end without bloating the seed.
 */
class CatalogFoodSeeder extends Seeder
{
    /**
     * @var array<int, array<string, mixed>>
     */
    private const FOODS = [
        ['name' => 'Banana', 'category' => 'fruits', 'calories' => 89, 'protein' => 1.1, 'carbohydrates' => 22.8, 'fat' => 0.3, 'fiber' => 2.6, 'sugar' => 12.2],
        ['name' => 'Apple', 'category' => 'fruits', 'calories' => 52, 'protein' => 0.3, 'carbohydrates' => 13.8, 'fat' => 0.2, 'fiber' => 2.4, 'sugar' => 10.4],
        ['name' => 'Strawberries', 'category' => 'fruits', 'calories' => 32, 'protein' => 0.7, 'carbohydrates' => 7.7, 'fat' => 0.3, 'fiber' => 2.0, 'sugar' => 4.9],
        ['name' => 'Broccoli', 'category' => 'vegetables', 'calories' => 34, 'protein' => 2.8, 'carbohydrates' => 6.6, 'fat' => 0.4, 'fiber' => 2.6, 'sugar' => 1.7],
        ['name' => 'Carrot', 'category' => 'vegetables', 'calories' => 41, 'protein' => 0.9, 'carbohydrates' => 9.6, 'fat' => 0.2, 'fiber' => 2.8, 'sugar' => 4.7],
        ['name' => 'Spinach', 'category' => 'vegetables', 'calories' => 23, 'protein' => 2.9, 'carbohydrates' => 3.6, 'fat' => 0.4, 'fiber' => 2.2, 'sugar' => 0.4],
        ['name' => 'Rolled Oats', 'brand' => 'Generic', 'category' => 'grains-cereals', 'calories' => 389, 'protein' => 16.9, 'carbohydrates' => 66.3, 'fat' => 6.9, 'fiber' => 10.6, 'sugar' => 1.0],
        ['name' => 'White Rice, cooked', 'category' => 'grains-cereals', 'calories' => 130, 'protein' => 2.7, 'carbohydrates' => 28.2, 'fat' => 0.3, 'fiber' => 0.4, 'sugar' => 0.1],
        ['name' => 'Wholemeal Bread', 'brand' => 'Generic', 'category' => 'bakery-breads', 'calories' => 247, 'protein' => 12.3, 'carbohydrates' => 41.3, 'fat' => 3.4, 'fiber' => 7.0, 'sugar' => 5.0, 'sodium' => 492],
        ['name' => 'Chicken Breast, cooked', 'category' => 'meat-poultry', 'calories' => 165, 'protein' => 31.0, 'carbohydrates' => 0, 'fat' => 3.6, 'sodium' => 74],
        ['name' => 'Egg, whole', 'category' => 'dairy-eggs', 'calories' => 155, 'protein' => 12.6, 'carbohydrates' => 1.1, 'fat' => 10.6, 'fiber' => 0, 'sugar' => 1.1, 'sodium' => 124],
        ['name' => 'Greek Yoghurt, plain', 'category' => 'dairy-eggs', 'calories' => 59, 'protein' => 10, 'carbohydrates' => 3.6, 'fat' => 0.4, 'sugar' => 3.2, 'sodium' => 36],
        ['name' => 'Semi-skimmed Milk', 'category' => 'dairy-eggs', 'calories' => 46, 'protein' => 3.4, 'carbohydrates' => 5.0, 'fat' => 1.0, 'sugar' => 5.0, 'sodium' => 45, 'base_unit' => 'ml'],
        ['name' => 'Almonds', 'category' => 'legumes-nuts', 'calories' => 579, 'protein' => 21.2, 'carbohydrates' => 21.6, 'fat' => 49.9, 'fiber' => 12.5, 'sugar' => 4.4, 'sodium' => 1],
        ['name' => 'Chickpeas, cooked', 'category' => 'legumes-nuts', 'calories' => 164, 'protein' => 8.9, 'carbohydrates' => 27.4, 'fat' => 2.6, 'fiber' => 7.6, 'sugar' => 4.8],
        ['name' => 'Gouda Cheese', 'category' => 'cheese', 'calories' => 356, 'protein' => 24.9, 'carbohydrates' => 2.2, 'fat' => 27.4, 'saturated_fat' => 17.6, 'sodium' => 819],
        ['name' => 'Olive Oil', 'category' => 'oils-fats', 'calories' => 884, 'protein' => 0, 'carbohydrates' => 0, 'fat' => 100, 'saturated_fat' => 13.8],
        ['name' => 'Tomato Ketchup', 'category' => 'sauces-condiments', 'calories' => 112, 'protein' => 1.1, 'carbohydrates' => 25.8, 'fat' => 0.1, 'sugar' => 21.3, 'sodium' => 907],
    ];

    public function run(): void
    {
        $categories = FoodCategory::pluck('id', 'slug');

        foreach (self::FOODS as $food) {
            $categorySlug = $food['category'] ?? null;

            Food::updateOrCreate(
                [
                    'user_id' => null,
                    'source' => 'catalog',
                    'name' => $food['name'],
                ],
                [
                    'category_id' => $categorySlug !== null
                        ? ($categories[$categorySlug] ?? null)
                        : null,
                    'base_unit' => $food['base_unit'] ?? 'g',
                    'base_amount' => 100,
                    'calories' => $food['calories'],
                    'protein' => $food['protein'],
                    'carbohydrates' => $food['carbohydrates'],
                    'fat' => $food['fat'],
                    'fiber' => $food['fiber'] ?? null,
                    'sugar' => $food['sugar'] ?? null,
                    'saturated_fat' => $food['saturated_fat'] ?? null,
                    'sodium' => $food['sodium'] ?? null,
                    'verified' => true,
                    'is_custom' => false,
                ]
            );
        }
    }
}