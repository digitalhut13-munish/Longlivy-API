<?php

namespace Tests\Feature;

use App\Contracts\Integrations\BarcodeLookupService;
use App\Models\Food;
use App\Models\Goal;
use App\Models\User;
use Database\Seeders\CatalogFoodSeeder;
use Database\Seeders\FoodCategorySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class NutritionCatalogApiTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(array $profile = []): User
    {
        $user = User::factory()->create();

        $user->profile()->create(array_merge([
            'first_name' => 'Test',
            'last_name' => 'User',
            'date_of_birth' => now()->subYears(30)->startOfYear()
                ->toDateString(),
            'gender' => 'male',
            'height' => 180,
            'current_weight' => 80,
            'activity_level' => 'moderate',
            'timezone' => 'UTC',
        ], $profile));

        return $user;
    }

    private function seedCatalog(): void
    {
        $this->seed(FoodCategorySeeder::class);
        $this->seed(CatalogFoodSeeder::class);
    }

    public function test_catalog_rows_are_marked_and_paginated(): void
    {
        Sanctum::actingAs(User::factory()->create());
        $this->seedCatalog();

        $this->getJson('/api/v1/foods')
            ->assertStatus(200)
            ->assertJsonPath('data.meta.current_page', 1)
            ->assertJsonPath('data.meta.per_page', 50)
            ->assertJsonPath('data.meta.total', 18)
            ->assertJsonPath('data.meta.last_page', 1);

        $banana = collect($this->getJson('/api/v1/foods')
            ->json('data.foods'))
            ->firstWhere('name', 'Banana');

        $this->assertSame('catalog', $banana['source']);
        $this->assertTrue($banana['verified']);
        $this->assertFalse($banana['is_custom']);
        $this->assertFalse($banana['owned']);
        $this->assertSame('Fruits', $banana['category']['name']);
        $this->assertSame('89.00', $banana['nutrients']['calories']);
    }

    public function test_scope_and_category_filter_the_results(): void
    {
        Sanctum::actingAs($user = User::factory()->create());
        $this->seedCatalog();

        Food::create([
            'user_id' => $user->id,
            'name' => 'My Power Shake',
            'is_custom' => true,
            'calories' => 100,
            'protein' => 5,
            'carbohydrates' => 5,
            'fat' => 5,
        ]);

        $catalog = $this->getJson('/api/v1/foods?scope=catalog')
            ->json('data.foods.*.name');

        $this->assertContains('Banana', $catalog);
        $this->assertNotContains('My Power Shake', $catalog);

        $mine = $this->getJson('/api/v1/foods?scope=mine')
            ->json('data.foods.*.name');

        $this->assertSame(['My Power Shake'], $mine);

        $fruits = $this->getJson('/api/v1/foods?category=fruits')
            ->json('data.foods.*.name');

        $this->assertContains('Banana', $fruits);
        $this->assertContains('Apple', $fruits);
        $this->assertNotContains('Broccoli', $fruits);
    }

    public function test_own_foods_are_ranked_before_the_catalog(): void
    {
        Sanctum::actingAs($user = User::factory()->create());
        $this->seedCatalog();

        Food::create([
            'user_id' => $user->id,
            'name' => 'Apple Pie',
            'is_custom' => true,
            'calories' => 265,
            'protein' => 2,
            'carbohydrates' => 40,
            'fat' => 11,
        ]);

        $names = $this->getJson('/api/v1/foods')
            ->json('data.foods.*.name');

        $this->assertSame('Apple Pie', $names[0]);
    }

    public function test_single_character_search_term_is_rejected(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->getJson('/api/v1/foods?q=a')
            ->assertStatus(422)
            ->assertJsonValidationErrors('q');
    }

    public function test_barcode_requires_ean_length(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->getJson('/api/v1/foods/barcode/123')
            ->assertStatus(422)
            ->assertJsonPath('success', false);
    }

    public function test_barcode_creates_and_reuses_a_catalog_food(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->app->instance(BarcodeLookupService::class, new class implements BarcodeLookupService
        {
            public function lookup(string $barcode): ?array
            {
                return [
                    'barcode' => '3017620422003',
                    'name' => 'Nutella',
                    'brand' => 'Ferrero',
                    'serving_size' => 15.0,
                    'unit' => 'g',
                    'calories' => 539.0,
                    'protein' => 6.3,
                    'carbohydrates' => 57.5,
                    'fat' => 30.9,
                    'fiber' => 3.4,
                    'sugar' => 56.3,
                    'sodium' => 0.0,
                    'source' => 'open_food_facts',
                ];
            }
        });

        $this->getJson('/api/v1/foods/barcode/3017620422003')
            ->assertStatus(201)
            ->assertJsonPath('found', true)
            ->assertJsonPath('data.needs_review', true)
            ->assertJsonPath('data.food.name', 'Nutella')
            ->assertJsonPath('data.food.source', 'open_food_facts')
            ->assertJsonPath('data.food.owned', false);

        $this->assertDatabaseHas('foods', [
            'name' => 'Nutella',
            'user_id' => null,
            'is_custom' => false,
            'verified' => true,
            'barcode' => '3017620422003',
        ]);

        // A second scan finds the cached catalog row locally (200).
        $this->getJson('/api/v1/foods/barcode/3017620422003')
            ->assertStatus(200)
            ->assertJsonPath('found', true);

        $this->assertDatabaseCount('foods', 1);
    }

    public function test_recognition_matches_catalog_foods_into_the_draft(): void
    {
        Sanctum::actingAs(User::factory()->create());
        $this->seedCatalog();

        $egg = Food::where('name', 'Egg, whole')->first();

        $draft = $this->postJson('/api/v1/nutrition/recognize', [
            'type' => 'text',
            'text' => '100 g egg for breakfast',
            'meal_type' => 'breakfast',
        ])
            ->assertStatus(200)
            ->assertJsonPath('data.needs_review', true)
            ->assertJsonPath('data.persisted', false)
            ->assertJsonPath('data.draft.provider', 'longlivy-keyword')
            ->assertJsonPath('data.draft.meal_type', 'breakfast');

        $this->assertSame(
            $egg->id,
            $draft->json('data.draft.items.0.food_id')
        );
    }

    public function test_recognition_limits_text_and_photos(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->postJson('/api/v1/nutrition/recognize', [
            'type' => 'text',
            'text' => str_repeat('a', 1001),
        ])->assertStatus(422)
            ->assertJsonValidationErrors('text');

        $this->postJson('/api/v1/nutrition/recognize', [
            'type' => 'photo',
        ])->assertStatus(422)
            ->assertJsonValidationErrors('image');
    }

    public function test_recognition_is_limited_to_twenty_per_hour(): void
    {
        Sanctum::actingAs(User::factory()->create());

        for ($i = 0; $i < 20; $i++) {
            $this->postJson('/api/v1/nutrition/recognize', [
                'type' => 'text',
                'text' => '100 g oats',
            ])->assertStatus(200);
        }

        $this->postJson('/api/v1/nutrition/recognize', [
            'type' => 'text',
            'text' => '100 g oats',
        ])->assertStatus(429);
    }

    public function test_editing_a_target_marks_it_as_manual(): void
    {
        Sanctum::actingAs($user = $this->makeUser());

        $this->postJson('/api/v1/energy/targets/calculate', [
            'direction' => 'maintain',
        ])->assertStatus(200);

        $goal = Goal::where('user_id', $user->id)
            ->where('goal_type', 'nutrition_calories')
            ->first();

        $this->putJson("/api/v1/goals/{$goal->id}", [
            'target_value' => 140,
        ])
            ->assertStatus(200)
            ->assertJsonPath('data.goal.source', 'manual');

        // Recalculating skips the manual target now.
        $this->postJson('/api/v1/energy/targets/calculate', [
            'direction' => 'maintain',
        ])
            ->assertStatus(200)
            ->assertJsonPath(
                'data.targets.nutrition_calories.source',
                'manual'
            )
            ->assertJsonPath(
                'data.targets.nutrition_calories.updated',
                false
            )
            ->assertJsonPath(
                'data.targets.nutrition_calories.reason',
                'manual_override'
            );
    }

    public function test_recalculation_can_overwrite_manual_targets(): void
    {
        Sanctum::actingAs($user = $this->makeUser());

        Goal::create([
            'user_id' => $user->id,
            'goal_type' => 'nutrition_calories',
            'name' => 'My own target',
            'target_value' => 1400,
            'unit' => 'kcal',
            'period' => 'day',
            'start_date' => now()->toDateString(),
            'active' => true,
            'source' => 'manual',
        ]);

        $this->postJson('/api/v1/energy/targets/calculate', [
            'direction' => 'maintain',
            'overwrite_manual' => true,
        ])
            ->assertStatus(200)
            ->assertJsonPath(
                'data.targets.nutrition_calories.value',
                2759
            )
            ->assertJsonPath(
                'data.targets.nutrition_calories.updated',
                true
            )
            ->assertJsonPath(
                'data.targets.nutrition_calories.source',
                'longlivy_calculated'
            );
    }

    public function test_weekly_change_kg_drives_the_adjustment(): void
    {
        Sanctum::actingAs($this->makeUser());

        $this->postJson('/api/v1/energy/targets/calculate', [
            'direction' => 'lose',
            'weekly_change_kg' => 0.5,
        ])
            ->assertStatus(200)
            ->assertJsonPath('data.adjustment', -550.0)
            ->assertJsonPath(
                'data.targets.nutrition_calories.value',
                2209
            );
    }

    public function test_weekly_change_kg_outside_the_range_is_rejected(): void
    {
        Sanctum::actingAs($this->makeUser());

        $this->postJson('/api/v1/energy/targets/calculate', [
            'direction' => 'lose',
            'weekly_change_kg' => 2.0,
        ])->assertStatus(422)
            ->assertJsonValidationErrors('weekly_change_kg');
    }

    public function test_meal_logged_at_keeps_utc_and_date_uses_profile_timezone(): void
    {
        Sanctum::actingAs($this->makeUser(['timezone' => 'Europe/Berlin']));

        $meal = $this->postJson('/api/v1/meals', [
            'meal_type' => 'dinner',
            'logged_at' => '2026-10-07T19:00:00+02:00',
        ])
            ->assertStatus(201)
            ->assertJsonPath('data.meal.date', '2026-10-07');

        $this->assertSame(
            '2026-10-07T17:00:00+00:00',
            $meal->json('data.meal.logged_at')
        );

        $stored = \App\Models\Meal::first();

        $this->assertSame('2026-10-07 17:00:00', $stored->logged_at->format('Y-m-d H:i:s'));
    }

    public function test_meal_date_is_honoured_instead_of_dropped(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->postJson('/api/v1/meals', [
            'meal_type' => 'lunch',
            'date' => '2026-10-05',
        ])
            ->assertStatus(201)
            ->assertJsonPath('data.meal.date', '2026-10-05');
    }

    public function test_food_categories_are_seeded_and_listed(): void
    {
        Sanctum::actingAs(User::factory()->create());
        $this->seed(FoodCategorySeeder::class);

        $categories = $this->getJson('/api/v1/food-categories')
            ->assertStatus(200)
            ->json('data.categories');

        $this->assertNotEmpty($categories);

        $fruits = collect($categories)->firstWhere('slug', 'fruits');

        $this->assertSame('Fruits', $fruits['name']);
    }
}