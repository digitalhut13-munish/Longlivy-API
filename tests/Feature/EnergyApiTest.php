<?php

namespace Tests\Feature;

use App\Models\Food;
use App\Models\Goal;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class EnergyApiTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Profile with fixed inputs: male, 30 years, 180 cm, 80 kg,
     * moderate activity.
     *
     * BMR (Mifflin-St Jeor) = 10*80 + 6.25*180 - 5*30 + 5 = 1780
     * Activity factor moderate = 1.55 -> TDEE = 2759
     * Everyday activity = 1780 * 0.55 = 979
     */
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

    private function makeFood(User $user): Food
    {
        return Food::create([
            'user_id' => $user->id,
            'name' => 'Test Food',
            'is_custom' => true,
            'base_unit' => 'g',
            'base_amount' => 100,
            'calories' => 100,
            'protein' => 10,
            'carbohydrates' => 10,
            'fat' => 10,
        ]);
    }

    public function test_bmr_endpoint_reports_incomplete_profile(): void
    {
        Sanctum::actingAs($user = User::factory()->create());

        $this->getJson('/api/v1/energy/bmr')
            ->assertStatus(200)
            ->assertJsonPath('data.ready', false)
            ->assertJsonPath('data.bmr', null)
            ->assertJsonPath('data.method', 'Mifflin-St Jeor')
            ->assertJsonPath('data.version', '1.0')
            ->assertJsonPath(
                'data.missing_inputs.0',
                'date_of_birth'
            );
    }

    public function test_bmr_and_tdee_are_calculated_with_mifflin_st_jeor(): void
    {
        Sanctum::actingAs($this->makeUser());

        $response = $this->getJson('/api/v1/energy/bmr')
            ->assertStatus(200)
            ->assertJsonPath('data.ready', true)
            ->assertJsonPath('data.bmr', 1780.0)
            ->assertJsonPath('data.tdee', 2759.0)
            ->assertJsonPath('data.everyday_activity', 979.0)
            ->assertJsonPath('data.activity_factor', 1.55)
            ->assertJsonPath('data.method', 'Mifflin-St Jeor');

        $inputs = $response->json('data.inputs');

        $this->assertSame(30, $inputs['age']);
        $this->assertSame(180.0, (float) $inputs['height']);
        $this->assertSame(80.0, (float) $inputs['weight']);
    }

    public function test_energy_balance_merges_components_and_intake(): void
    {
        Sanctum::actingAs($user = $this->makeUser());

        $food = $this->makeFood($user);

        $this->postJson('/api/v1/nutrition/log', [
            'food_id' => $food->id,
            'quantity' => 1650,
            'unit' => 'g',
            'meal_type' => 'lunch',
            'logged_at' => now()->subDays(2)->toIso8601String(),
        ])->assertStatus(201);

        $date = now()->subDays(2)->toDateString();

        $balance = $this->getJson(
            '/api/v1/energy/balance?date='.$date
        )
            ->assertStatus(200)
            ->assertJsonPath('data.date', $date)
            ->assertJsonPath('data.intake', 1650.0)
            ->assertJsonPath('data.consumption.bmr', 1780.0)
            ->assertJsonPath('data.consumption.everyday_activity', 979.0)
            ->assertJsonPath('data.consumption.total', 2759.0)
            ->assertJsonPath('data.goal.status', 'no_goal')
            ->assertJsonPath('data.difference', -1109.0);

        $component = collect($balance->json('data.components'))
            ->firstWhere('component', 'bmr');

        $this->assertSame('longlivy_calculated', $component['source']);
        $this->assertSame('Mifflin-St Jeor', $component['calculation_method']);
        $this->assertSame('1.0', $component['calculation_version']);
    }

    public function test_calculated_targets_fill_the_daily_goal(): void
    {
        Sanctum::actingAs($user = $this->makeUser());

        $this->postJson('/api/v1/energy/targets/calculate', [
            'direction' => 'maintain',
        ])
            ->assertStatus(200)
            ->assertJsonPath('data.tdee', 2759.0)
            ->assertJsonPath(
                'data.targets.nutrition_calories.source',
                'longlivy_calculated'
            )
            ->assertJsonPath(
                'data.targets.nutrition_calories.value',
                2759
            );

        $goal = $this->getJson('/api/v1/energy/balance')
            ->assertStatus(200)
            ->assertJsonPath('data.goal.calories', 2759.0)
            ->assertJsonPath('data.goal.status', 'below')
            ->assertJsonPath('data.goal.remaining', 2759.0)
            ->assertJsonPath('data.goal.over_by', null);

        $this->assertDatabaseHas('goals', [
            'user_id' => $user->id,
            'goal_type' => 'nutrition_calories',
            'source' => 'longlivy_calculated',
        ]);
    }

    public function test_lose_direction_applies_configured_deficit(): void
    {
        Sanctum::actingAs($user = $this->makeUser());

        $this->postJson('/api/v1/energy/targets/calculate', [
            'direction' => 'lose',
        ])
            ->assertStatus(200)
            ->assertJsonPath('data.adjustment', -500)
            ->assertJsonPath(
                'data.targets.nutrition_calories.value',
                2259
            );
    }

    public function test_calculated_targets_never_overwrite_manual_goals(): void
    {
        Sanctum::actingAs($user = $this->makeUser());

        Goal::create([
            'user_id' => $user->id,
            'goal_type' => 'nutrition_calories',
            'name' => 'My own calorie target',
            'target_value' => 1800,
            'unit' => 'kcal',
            'period' => 'day',
            'start_date' => now()->toDateString(),
            'active' => true,
            'source' => 'manual',
        ]);

        $this->postJson('/api/v1/energy/targets/calculate')
            ->assertStatus(200)
            ->assertJsonPath(
                'data.targets.nutrition_calories.source',
                'manual'
            )
            ->assertJsonPath(
                'data.targets.nutrition_calories.value',
                1800
            )
            ->assertJsonPath(
                'data.targets.nutrition_calories.updated',
                false
            )
            ->assertJsonPath(
                'data.targets.nutrition_protein.source',
                'longlivy_calculated'
            );

        // The balance prefers the manual target over the calculated
        // one.
        $this->getJson('/api/v1/energy/balance')
            ->assertStatus(200)
            ->assertJsonPath('data.goal.calories', 1800.0);
    }

    public function test_exceeded_goal_shows_over_by_and_never_remaining(): void
    {
        Sanctum::actingAs($user = $this->makeUser());

        $food = $this->makeFood($user);

        $this->postJson('/api/v1/nutrition/log', [
            'food_id' => $food->id,
            'quantity' => 1250,
            'unit' => 'g',
            'meal_type' => 'dinner',
        ])->assertStatus(201);

        Goal::create([
            'user_id' => $user->id,
            'goal_type' => 'nutrition_calories',
            'name' => 'Daily calories',
            'target_value' => 1000,
            'unit' => 'kcal',
            'period' => 'day',
            'start_date' => now()->subDay()->toDateString(),
            'active' => true,
            'source' => 'manual',
        ]);

        $this->getJson('/api/v1/energy/balance')
            ->assertStatus(200)
            ->assertJsonPath('data.goal.status', 'exceeded')
            ->assertJsonPath('data.goal.over_by', 250.0)
            ->assertJsonPath('data.goal.remaining', null)
            ->assertJsonPath('data.ring.state', 'exceeded');
    }

    public function test_meal_changes_update_the_balance_automatically(): void
    {
        Sanctum::actingAs($user = $this->makeUser());

        $food = $this->makeFood($user);

        $this->postJson('/api/v1/nutrition/log', [
            'food_id' => $food->id,
            'quantity' => 500,
            'unit' => 'g',
            'meal_type' => 'breakfast',
        ])->assertStatus(201);

        $this->getJson('/api/v1/energy/balance')
            ->assertStatus(200)
            ->assertJsonPath('data.intake', 500.0);

        $this->postJson('/api/v1/nutrition/log', [
            'food_id' => $food->id,
            'quantity' => 200,
            'unit' => 'g',
            'meal_type' => 'breakfast',
        ])->assertStatus(201);

        $this->getJson('/api/v1/energy/balance')
            ->assertStatus(200)
            ->assertJsonPath('data.intake', 700.0);
    }

    public function test_weight_change_updates_the_bmr_component(): void
    {
        Sanctum::actingAs($user = $this->makeUser());

        $this->getJson('/api/v1/energy/balance')
            ->assertStatus(200)
            ->assertJsonPath('data.consumption.bmr', 1780.0);

        $this->postJson('/api/v1/weight-logs', [
            'weight' => 90,
            'logged_at' => now()->toIso8601String(),
        ])->assertStatus(201);

        // 10*90 + 6.25*180 - 5*30 + 5 = 1880
        $this->getJson('/api/v1/energy/balance')
            ->assertStatus(200)
            ->assertJsonPath('data.consumption.bmr', 1880.0);
    }

    public function test_profile_update_refreshes_the_current_balance(): void
    {
        Sanctum::actingAs($user = $this->makeUser());

        $this->getJson('/api/v1/energy/balance')
            ->assertStatus(200)
            ->assertJsonPath('data.consumption.bmr', 1780.0);

        $this->putJson('/api/v1/profile', [
            'gender' => 'female',
        ])
            ->assertStatus(200);

        // 10*80 + 6.25*180 - 5*30 - 161 = 1619
        $this->getJson('/api/v1/energy/balance')
            ->assertStatus(200)
            ->assertJsonPath('data.consumption.bmr', 1619.0);
    }

    public function test_targets_require_a_complete_profile(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->postJson('/api/v1/energy/targets/calculate')
            ->assertStatus(422)
            ->assertJsonValidationErrors('profile');
    }
}
