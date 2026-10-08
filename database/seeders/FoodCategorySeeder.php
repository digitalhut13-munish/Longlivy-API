<?php

namespace Database\Seeders;

use App\Models\FoodCategory;
use Illuminate\Database\Seeder;

class FoodCategorySeeder extends Seeder
{
    /**
     * Categories used by the food database and the mobile app's
     * food picker. The slug is the stable identifier.
     *
     * @var array<int, array{name: string, slug: string}>
     */
    private const CATEGORIES = [
        ['name' => 'Grains & Cereals', 'slug' => 'grains-cereals'],
        ['name' => 'Bakery & Breads', 'slug' => 'bakery-breads'],
        ['name' => 'Fruits', 'slug' => 'fruits'],
        ['name' => 'Vegetables', 'slug' => 'vegetables'],
        ['name' => 'Meat & Poultry', 'slug' => 'meat-poultry'],
        ['name' => 'Fish & Seafood', 'slug' => 'fish-seafood'],
        ['name' => 'Dairy & Eggs', 'slug' => 'dairy-eggs'],
        ['name' => 'Legumes & Nuts', 'slug' => 'legumes-nuts'],
        ['name' => 'Cheese', 'slug' => 'cheese'],
        ['name' => 'Snacks & Sweets', 'slug' => 'snacks-sweets'],
        ['name' => 'Beverages', 'slug' => 'beverages'],
        ['name' => 'Fast Food', 'slug' => 'fast-food'],
        ['name' => 'Oils & Fats', 'slug' => 'oils-fats'],
        ['name' => 'Sauces & Condiments', 'slug' => 'sauces-condiments'],
        ['name' => 'Supplements', 'slug' => 'supplements'],
        ['name' => 'Other', 'slug' => 'other'],
    ];

    public function run(): void
    {
        foreach (self::CATEGORIES as $position => $category) {
            FoodCategory::updateOrCreate(
                ['slug' => $category['slug']],
                [
                    'name' => $category['name'],
                    'sort_order' => $position,
                    'active' => true,
                ]
            );
        }
    }
}
