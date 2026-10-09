<?php

namespace Tests\Feature;

use App\Models\Food;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ContractFixesApiTest extends TestCase
{
    use RefreshDatabase;

    // -----------------------------------------------------------------------
    // #25 pagination on list endpoints
    // -----------------------------------------------------------------------

    public function test_weight_logs_paginate_with_meta(): void
    {
        Sanctum::actingAs($user = User::factory()->create());

        foreach (range(1, 55) as $i) {
            $user->weightLogs()->create([
                'weight' => 70 + $i * 0.1,
                'unit' => 'kg',
                'logged_at' => Carbon::now()->subMinutes($i),
                'date' => Carbon::now()->subDays($i)->toDateString(),
            ]);
        }

        $page1 = $this->getJson('/api/v1/weight-logs?page=1&per_page=20')
            ->assertStatus(200)
            ->assertJsonPath('data.meta.current_page', 1)
            ->assertJsonPath('data.meta.per_page', 20)
            ->assertJsonPath('data.meta.total', 55)
            ->assertJsonPath('data.meta.last_page', 3)
            ->assertJsonCount(20, 'data.weight_logs');

        $page2 = $this->getJson('/api/v1/weight-logs?page=2&per_page=20')
            ->assertStatus(200)
            ->assertJsonPath('data.meta.current_page', 2)
            ->assertJsonPath('data.meta.total', 55)
            ->assertJsonCount(20, 'data.weight_logs');

        $this->assertNotEquals(
            $page1->json('data.weight_logs.0.id'),
            $page2->json('data.weight_logs.0.id')
        );

        $this->getJson('/api/v1/weight-logs?per_page=200')
            ->assertStatus(422);

        $this->getJson('/api/v1/weight-logs?page=0')
            ->assertStatus(422);
    }

    public function test_fastings_paginate_with_meta(): void
    {
        Sanctum::actingAs($user = User::factory()->create());

        foreach (range(1, 55) as $i) {
            $user->fastings()->create([
                'fasting_type' => '16:8',
                'planned_hours' => 16,
                'planned_minutes' => 0,
                'status' => 'completed',
                'started_at' => Carbon::now()->subHours($i * 3),
                'actual_hours' => 16.5,
                'date' => Carbon::now()->subDays($i)->toDateString(),
            ]);
        }

        $this->getJson('/api/v1/fasting?page=2&per_page=20')
            ->assertStatus(200)
            ->assertJsonPath('data.meta.current_page', 2)
            ->assertJsonPath('data.meta.per_page', 20)
            ->assertJsonPath('data.meta.total', 55)
            ->assertJsonPath('data.meta.last_page', 3)
            ->assertJsonCount(20, 'data.fastings');

        $this->getJson('/api/v1/fasting?per_page=20')
            ->assertJsonCount(20, 'data.fastings');
    }

    public function test_meals_paginate_with_meta(): void
    {
        Sanctum::actingAs($user = User::factory()->create());

        foreach (range(1, 55) as $i) {
            $user->meals()->create([
                'meal_type' => 'breakfast',
                'name' => "Meal {$i}",
                'logged_at' => Carbon::now()->subMinutes($i),
                'date' => Carbon::now()->subDays($i)->toDateString(),
            ]);
        }

        $this->getJson('/api/v1/meals?page=2&per_page=20')
            ->assertStatus(200)
            ->assertJsonPath('data.meta.current_page', 2)
            ->assertJsonPath('data.meta.total', 55)
            ->assertJsonCount(20, 'data.meals');
    }

    public function test_meditation_sessions_paginate_with_meta(): void
    {
        Sanctum::actingAs($user = User::factory()->create());

        foreach (range(1, 55) as $i) {
            $user->meditationSessions()->create([
                'type' => 'free',
                'status' => 'completed',
                'planned_minutes' => 10,
                'started_at' => Carbon::now()->subMinutes($i * 10),
                'date' => Carbon::now()->subDays($i)->toDateString(),
            ]);
        }

        $this->getJson('/api/v1/meditation/sessions?page=2&per_page=20')
            ->assertStatus(200)
            ->assertJsonPath('data.meta.current_page', 2)
            ->assertJsonPath('data.meta.total', 55)
            ->assertJsonCount(20, 'data.sessions');
    }

    public function test_recipes_paginate_with_meta(): void
    {
        Sanctum::actingAs($user = User::factory()->create());

        foreach (range(1, 55) as $i) {
            $user->recipes()->create([
                'name' => "Recipe {$i}",
            ]);
        }

        $this->getJson('/api/v1/recipes?page=2&per_page=20')
            ->assertStatus(200)
            ->assertJsonPath('data.meta.current_page', 2)
            ->assertJsonPath('data.meta.total', 55)
            ->assertJsonCount(20, 'data.recipes');
    }

    public function test_favorites_paginate_with_meta(): void
    {
        Sanctum::actingAs($user = User::factory()->create());

        foreach (range(1, 55) as $i) {
            $food = $user->foods()->create([
                'name' => "Food {$i}",
            ]);

            $user->favorites()->create([
                'favoritable_type' => Food::class,
                'favoritable_id' => $food->id,
            ]);
        }

        $this->getJson('/api/v1/favorites?page=2&per_page=20')
            ->assertStatus(200)
            ->assertJsonPath('data.meta.current_page', 2)
            ->assertJsonPath('data.meta.total', 55)
            ->assertJsonCount(20, 'data.favorites');
    }

    public function test_pagination_defaults_to_fifty_per_page(): void
    {
        Sanctum::actingAs($user = User::factory()->create());

        foreach (range(1, 55) as $i) {
            $user->weightLogs()->create([
                'weight' => 70 + $i * 0.1,
                'logged_at' => Carbon::now()->subMinutes($i),
                'date' => Carbon::now()->subDays($i)->toDateString(),
            ]);
        }

        $this->getJson('/api/v1/weight-logs?page=2')
            ->assertStatus(200)
            ->assertJsonPath('data.meta.per_page', 50)
            ->assertJsonPath('data.meta.total', 55)
            ->assertJsonCount(5, 'data.weight_logs');
    }

    // -----------------------------------------------------------------------
    // #28 a) goal defaults
    // -----------------------------------------------------------------------

    public function test_goals_default_to_active_and_manual_source(): void
    {
        Sanctum::actingAs($user = User::factory()->create());

        $this->postJson('/api/v1/goals', [
            'goal_type' => 'nutrition_calories',
            'name' => 'Daily calories',
            'target_value' => 2000,
            'unit' => 'kcal',
            'period' => 'day',
            'start_date' => '2026-10-01',
        ])
            ->assertStatus(201)
            ->assertJsonPath('data.goal.active', true)
            ->assertJsonPath('data.goal.source', 'manual');

        $this->assertDatabaseHas('goals', [
            'user_id' => $user->id,
            'active' => true,
            'source' => 'manual',
        ]);
    }

    // -----------------------------------------------------------------------
    // #28 b) reminder time + days_of_week array
    // -----------------------------------------------------------------------

    public function test_reminder_accepts_days_of_week_array_and_normalises_time(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $reminder = $this->postJson('/api/v1/meditation/reminders', [
            'time' => '07:30:00',
            'days_of_week' => [1, 2, 5],
            'label' => 'Morning sit',
        ])
            ->assertStatus(201)
            ->assertJsonPath('data.reminder.time', '07:30')
            ->assertJsonPath('data.reminder.days', [1, 2, 5])
            ->json('data.reminder');

        $this->assertDatabaseHas('meditation_reminders', [
            'id' => $reminder['id'],
            'days_of_week' => '1,2,5',
        ]);

        $this->putJson(
            "/api/v1/meditation/reminders/{$reminder['id']}",
            ['time' => '08:00:00']
        )
            ->assertStatus(200)
            ->assertJsonPath('data.reminder.time', '08:00');
    }

    public function test_reminder_rejects_out_of_range_days_of_week_array(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->postJson('/api/v1/meditation/reminders', [
            'time' => '07:30',
            'days_of_week' => [1, 8],
        ])->assertStatus(422);
    }

    // -----------------------------------------------------------------------
    // #28 c) fasting hours are numbers
    // -----------------------------------------------------------------------

    public function test_fasting_hours_are_numeric(): void
    {
        Sanctum::actingAs($user = User::factory()->create());

        $started = $this->postJson('/api/v1/fasting', [
            'fasting_type' => '16:8',
            'planned_hours' => 16,
            'started_at' => Carbon::now()->subHours(16)->toISOString(),
        ])->assertStatus(201)->json('data.fasting');

        $end = $this->postJson(
            "/api/v1/fasting/{$started['id']}/end",
            []
        )->assertStatus(200)->json('data.fasting');

        $this->assertIsFloat($end['actual_hours']);
        $this->assertIsFloat($end['elapsed_hours']);
        $this->assertSame(16.0, $end['actual_hours']);

        $this->assertDatabaseHas('fastings', [
            'user_id' => $user->id,
            'status' => 'completed',
        ]);
    }

    // -----------------------------------------------------------------------
    // #28 d) bearer token lifetime
    // -----------------------------------------------------------------------

    public function test_login_returns_an_expiring_token(): void
    {
        $user = User::factory()->create([
            'email' => 'expiry@example.com',
            'password' => Hash::make('secret123'),
        ]);

        $data = $this->postJson('/api/v1/auth/login', [
            'email' => 'expiry@example.com',
            'password' => 'secret123',
        ])
            ->assertStatus(200)
            ->assertJsonPath('data.token_type', 'Bearer')
            ->json('data');

        $this->assertNotEmpty($data['token']);

        $expiresAt = Carbon::parse($data['expires_at']);

        $this->assertGreaterThan(
            Carbon::now()->addDays(29),
            $expiresAt
        );
        $this->assertLessThanOrEqual(
            Carbon::now()->addDays(31),
            $expiresAt
        );

        $this->assertDatabaseHas('personal_access_tokens', [
            'tokenable_id' => $user->id,
            'name' => 'longlivy-mobile',
        ]);
        $this->assertSame(
            30,
            (int) round(
                Carbon::now()->diffInDays(
                    $user->tokens()->first()->expires_at
                )
            )
        );
    }
}