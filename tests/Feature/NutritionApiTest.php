<?php

namespace Tests\Feature;

use App\Models\Food;
use App\Models\Goal;
use App\Models\Meal;
use App\Models\Recipe;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class NutritionApiTest extends TestCase
{
    use RefreshDatabase;

    private function makeFood(
        User $user,
        array $overrides = []
    ): Food {
        return Food::create(array_merge([
            'user_id' => $user->id,
            'name' => 'Rolled Oats',
            'brand' => 'Bulk',
            'is_custom' => true,
            'base_unit' => 'g',
            'base_amount' => 100,
            'calories' => 250,
            'protein' => 10,
            'carbohydrates' => 40,
            'fat' => 5,
            'fiber' => 8,
            'grams_per_unit' => 40,
            'serving_amount' => 50,
        ], $overrides));
    }

    public function test_custom_food_can_be_created_and_updated(): void
    {
        Sanctum::actingAs($user = User::factory()->create());

        $response = $this->postJson('/api/v1/foods', [
            'name' => 'Greek Yoghurt',
            'calories' => 59,
            'protein' => 10,
            'carbohydrates' => 3.6,
            'fat' => 0.4,
        ])
            ->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.food.is_custom', true)
            ->assertJsonPath('data.food.source', 'manual');

        $foodId = $response->json('data.food.id');

        $this->putJson("/api/v1/foods/{$foodId}", [
            'calories' => 65,
        ])
            ->assertStatus(200)
            ->assertJsonPath('data.food.nutrients.calories', '65.00');

        $this->assertDatabaseHas('foods', [
            'id' => $foodId,
            'calories' => 65,
        ]);
    }

    public function test_food_search_hides_other_users_custom_foods(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();

        $this->makeFood($owner, ['name' => 'Secret Snack']);
        $this->makeFood($other, ['name' => 'Rolled Oats']);
        Food::create([
            'name' => 'Public Oats',
            'is_custom' => false,
            'base_unit' => 'g',
            'base_amount' => 100,
            'calories' => 100,
            'protein' => 0,
            'carbohydrates' => 0,
            'fat' => 0,
        ]);

        Sanctum::actingAs($other);

        $names = $this->getJson('/api/v1/foods')
            ->assertStatus(200)
            ->json('data.foods.*.name');

        $this->assertContains('Public Oats', $names);
        $this->assertContains('Rolled Oats', $names);
        $this->assertNotContains('Secret Snack', $names);

        $mine = $this->getJson('/api/v1/foods?mine=1')
            ->assertStatus(200)
            ->json('data.foods.*.name');

        $this->assertSame(['Rolled Oats'], $mine);
    }

    public function test_global_food_cannot_be_modified(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $global = Food::create([
            'name' => 'Public Oats',
            'is_custom' => false,
            'base_unit' => 'g',
            'base_amount' => 100,
            'calories' => 100,
            'protein' => 0,
            'carbohydrates' => 0,
            'fat' => 0,
        ]);

        $this->putJson("/api/v1/foods/{$global->id}", [
            'calories' => 1,
        ])->assertStatus(403);

        $this->deleteJson("/api/v1/foods/{$global->id}")
            ->assertStatus(403);
    }

    public function test_unknown_barcode_returns_not_found(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->getJson('/api/v1/foods/barcode/9999999999999')
            ->assertStatus(404)
            ->assertJsonPath('found', false)
            ->assertJsonPath('data.barcode', '9999999999999');
    }

    public function test_quick_log_snapshots_food_nutrients(): void
    {
        Sanctum::actingAs($user = User::factory()->create());

        $food = $this->makeFood($user);

        $this->postJson('/api/v1/nutrition/log', [
            'food_id' => $food->id,
            'quantity' => 150,
            'unit' => 'g',
            'meal_type' => 'breakfast',
        ])
            ->assertStatus(201)
            ->assertJsonPath('data.meal.totals.calories', '375.00')
            ->assertJsonPath('data.meal.totals.protein', '15.00')
            ->assertJsonPath('data.meal.totals.carbohydrates', '60.00')
            ->assertJsonPath('data.meal.totals.fat', '7.50')
            ->assertJsonPath('data.meal.totals.fiber', '12.00')
            ->assertJsonPath('data.active_fasting', null);

        $this->assertDatabaseCount('meals', 1);
    }

    public function test_quick_log_appends_to_existing_meal(): void
    {
        Sanctum::actingAs($user = User::factory()->create());

        $food = $this->makeFood($user);

        $payload = [
            'food_id' => $food->id,
            'quantity' => 100,
            'unit' => 'g',
            'meal_type' => 'lunch',
        ];

        $this->postJson('/api/v1/nutrition/log', $payload)
            ->assertStatus(201);

        $this->postJson('/api/v1/nutrition/log', $payload)
            ->assertStatus(201)
            ->assertJsonPath('data.meal.totals.calories', '500.00');

        $this->assertDatabaseCount('meals', 1);
        $this->assertDatabaseCount('meal_items', 2);
    }

    public function test_manual_log_requires_calories_without_food(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->postJson('/api/v1/nutrition/log', [
            'name' => 'Mystery Leftovers',
            'quantity' => 1,
            'meal_type' => 'dinner',
        ])->assertStatus(422)
            ->assertJsonValidationErrors('calories');
    }

    public function test_nutrition_day_flags_exceeded_instead_of_remaining(): void
    {
        Sanctum::actingAs($user = User::factory()->create());

        $food = $this->makeFood($user);

        $this->postJson('/api/v1/nutrition/log', [
            'food_id' => $food->id,
            'quantity' => 600,
            'unit' => 'g',
            'meal_type' => 'lunch',
            'logged_at' => '2026-10-07T12:00:00Z',
        ])->assertStatus(201);

        Goal::forceCreate([
            'user_id' => $user->id,
            'goal_type' => 'nutrition_calories',
            'name' => 'Daily calories',
            'target_value' => 1000,
            'unit' => 'kcal',
            'period' => 'day',
            'start_date' => '2026-10-01',
            'active' => true,
            'source' => 'manual',
        ]);

        $day = $this->getJson('/api/v1/nutrition/day?date=2026-10-07')
            ->assertStatus(200);

        $this->assertSame(1500.0, (float) $day->json('data.intake.calories'));

        $calories = collect($day->json('data.nutrients'))
            ->firstWhere('nutrient', 'calories');

        $this->assertSame('exceeded', $calories['status']);
        $this->assertNull($calories['remaining']);
        $this->assertSame(500.0, (float) $calories['over_by']);
        $this->assertSame('manual', $calories['source']);
    }

    public function test_nutrition_day_without_goal_reports_no_goal(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $day = $this->getJson('/api/v1/nutrition/day')
            ->assertStatus(200);

        $calories = collect($day->json('data.nutrients'))
            ->firstWhere('nutrient', 'calories');

        $this->assertSame('no_goal', $calories['status']);
        $this->assertNull($calories['target']);
        $this->assertSame(0.0, (float) $calories['current']);
    }

    public function test_recognition_returns_unpersisted_draft(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->postJson('/api/v1/nutrition/recognize', [
            'type' => 'text',
            'text' => '2 eggs and 1 slice of toast',
            'meal_type' => 'breakfast',
        ])
            ->assertStatus(200)
            ->assertJsonPath('data.needs_review', true)
            ->assertJsonPath('data.persisted', false)
            ->assertJsonPath('success', true);

        $this->assertDatabaseCount('meals', 0);
        $this->assertDatabaseCount('meal_items', 0);
    }

    public function test_recipe_computes_totals_and_per_serving(): void
    {
        Sanctum::actingAs($user = User::factory()->create());

        $food = $this->makeFood($user);

        $created = $this->postJson('/api/v1/recipes', [
            'name' => 'Overnight Oats',
            'servings' => 2,
            'items' => [
                [
                    'food_id' => $food->id,
                    'quantity' => 200,
                    'unit' => 'g',
                ],
                [
                    'name' => 'Honey drizzle',
                    'quantity' => 1,
                    'unit' => 'serving',
                    'calories' => 60,
                    'protein' => 0,
                    'carbohydrates' => 16,
                    'fat' => 0,
                ],
            ],
        ])
            ->assertStatus(201)
            ->assertJsonPath('data.recipe.totals.calories', '560.00')
            ->assertJsonPath('data.recipe.items.0.name', 'Rolled Oats')
            ->assertJsonPath('data.recipe.items.1.name', 'Honey drizzle');

        $this->assertSame(
            280.0,
            (float) $created->json('data.recipe.per_serving.calories')
        );
    }

    public function test_favorites_can_be_added_listed_and_removed(): void
    {
        Sanctum::actingAs($user = User::factory()->create());

        $food = $this->makeFood($user);

        $favorite = $this->postJson('/api/v1/favorites', [
            'type' => 'food',
            'id' => $food->id,
        ])
            ->assertStatus(201)
            ->assertJsonPath('data.favorite.type', 'food')
            ->assertJsonPath('data.favorite.target.name', 'Rolled Oats');

        $favoriteId = $favorite->json('data.favorite.id');

        $this->getJson('/api/v1/favorites?type=food')
            ->assertStatus(200)
            ->assertJsonCount(1, 'data.favorites');

        $this->getJson('/api/v1/favorites?type=meal')
            ->assertStatus(200)
            ->assertJsonCount(0, 'data.favorites');

        $this->deleteJson("/api/v1/favorites/{$favoriteId}")
            ->assertStatus(200);

        $this->assertDatabaseCount('favorites', 0);
    }

    public function test_user_cannot_access_another_users_meal_or_recipe(): void
    {
        $owner = User::factory()->create();

        $meal = Meal::create([
            'user_id' => $owner->id,
            'meal_type' => 'lunch',
            'logged_at' => now(),
            'date' => now()->toDateString(),
        ]);

        $recipe = Recipe::create([
            'user_id' => $owner->id,
            'name' => 'Owner recipe',
        ]);

        Sanctum::actingAs(User::factory()->create());

        $this->getJson("/api/v1/meals/{$meal->id}")
            ->assertStatus(403);

        $this->putJson("/api/v1/meals/{$meal->id}", [
            'name' => 'Hacked',
        ])->assertStatus(403);

        $this->getJson("/api/v1/recipes/{$recipe->id}")
            ->assertStatus(403);

        $this->deleteJson("/api/v1/recipes/{$recipe->id}")
            ->assertStatus(403);
    }

    public function test_meal_item_crud_recomputes_totals(): void
    {
        Sanctum::actingAs($user = User::factory()->create());

        $food = $this->makeFood($user);

        $meal = $this->postJson('/api/v1/meals', [
            'meal_type' => 'dinner',
            'logged_at' => '2026-10-07T19:00:00Z',
            'items' => [
                [
                    'food_id' => $food->id,
                    'quantity' => 100,
                    'unit' => 'g',
                ],
            ],
        ])
            ->assertStatus(201)
            ->assertJsonPath('data.meal.totals.calories', '250.00');

        $mealId = $meal->json('data.meal.id');
        $itemId = $meal->json('data.meal.items.0.id');

        $this->putJson("/api/v1/meals/{$mealId}/items/{$itemId}", [
            'quantity' => 200,
        ])
            ->assertStatus(200)
            ->assertJsonPath('data.meal.totals.calories', '500.00');

        $this->deleteJson("/api/v1/meals/{$mealId}/items/{$itemId}")
            ->assertStatus(200)
            ->assertJsonPath('data.meal.totals.calories', '0.00');

        $this->assertDatabaseCount('meal_items', 0);
    }

    public function test_empty_meal_update_is_rejected(): void
    {
        Sanctum::actingAs($user = User::factory()->create());

        $meal = Meal::create([
            'user_id' => $user->id,
            'meal_type' => 'snack',
            'logged_at' => now(),
            'date' => now()->toDateString(),
        ]);

        $this->putJson("/api/v1/meals/{$meal->id}", [])
            ->assertStatus(422)
            ->assertJsonPath('message', 'No updatable fields received.');
    }
}
