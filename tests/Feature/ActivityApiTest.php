<?php

namespace Tests\Feature;

use App\Models\Activity;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ActivityApiTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(): User
    {
        $user = User::factory()->create();

        $user->profile()->create([
            'first_name' => 'Test',
            'last_name' => 'User',
            'date_of_birth' => now()->subYears(30)->startOfYear()
                ->toDateString(),
            'gender' => 'male',
            'height' => 180,
            'current_weight' => 80,
            'activity_level' => 'moderate',
            'timezone' => 'UTC',
        ]);

        return $user;
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'client_id' => 'act_1728380000000_ab12',
            'type' => 'running',
            'source' => 'tracked',
            'started_at' => '2026-10-08T06:30:00+00:00',
            'ended_at' => '2026-10-08T07:05:00+00:00',
            'active_seconds' => 2040,
            'paused_seconds' => 60,
            'distance_meters' => 6120,
            'calories_kcal' => 412,
            'calculation_method' => 'met_v2',
            'avg_heart_rate' => 158,
            'steps' => 8240,
            'route' => [
                'encoding' => 'polyline5',
                'points' => 'o~h`I_jnpA...',
                'point_count' => 812,
            ],
            'notes' => null,
        ], $overrides);
    }

    public function test_activity_can_be_created(): void
    {
        Sanctum::actingAs($user = $this->makeUser());

        $this->postJson('/api/v1/activities', $this->payload())
            ->assertStatus(201)
            ->assertJsonPath('data.activity.client_id', 'act_1728380000000_ab12')
            ->assertJsonPath('data.activity.calories_kcal', 412)
            ->assertJsonPath('data.activity.route.point_count', 812);

        $this->assertDatabaseHas('activities', [
            'user_id' => $user->id,
            'client_id' => 'act_1728380000000_ab12',
        ]);
    }

    public function test_duplicate_client_id_returns_existing_record(): void
    {
        Sanctum::actingAs($user = $this->makeUser());

        $this->postJson('/api/v1/activities', $this->payload())
            ->assertStatus(201);

        $this->postJson('/api/v1/activities', $this->payload([
            'calories_kcal' => 999,
        ]))
            ->assertStatus(200)
            ->assertJsonPath('data.activity.calories_kcal', 999);

        $this->assertDatabaseCount('activities', 1);
    }

    public function test_activity_validation_rules(): void
    {
        Sanctum::actingAs($this->makeUser());

        $this->postJson('/api/v1/activities', $this->payload([
            'ended_at' => '2026-10-08T06:00:00+00:00',
        ]))->assertStatus(422)
            ->assertJsonValidationErrors('ended_at');

        $this->postJson('/api/v1/activities', $this->payload([
            'calories_kcal' => 20000,
        ]))->assertStatus(422)
            ->assertJsonValidationErrors('calories_kcal');

        $this->postJson('/api/v1/activities', $this->payload([
            'type' => 'paragliding',
        ]))->assertStatus(422)
            ->assertJsonValidationErrors('type');

        $this->postJson('/api/v1/activities', $this->payload([
            'started_at' => '2026-10-08T00:00:00+00:00',
            'ended_at' => '2026-10-09T01:00:00+00:00',
        ]))->assertStatus(422)
            ->assertJsonValidationErrors('ended_at');
    }

    public function test_activity_list_filters_and_paginates(): void
    {
        Sanctum::actingAs($user = $this->makeUser());

        $this->postJson('/api/v1/activities', $this->payload([
            'client_id' => 'act_1',
            'type' => 'running',
        ]))->assertStatus(201);

        $this->postJson('/api/v1/activities', $this->payload([
            'client_id' => 'act_2',
            'type' => 'cycling',
            'started_at' => '2026-10-06T06:30:00+00:00',
            'ended_at' => '2026-10-06T07:05:00+00:00',
        ]))->assertStatus(201);

        $this->getJson('/api/v1/activities')
            ->assertStatus(200)
            ->assertJsonCount(2, 'data.activities')
            ->assertJsonPath('data.meta.total', 2);

        $this->getJson('/api/v1/activities?type=running')
            ->assertJsonCount(1, 'data.activities')
            ->assertJsonPath('data.activities.0.type', 'running');

        $this->getJson('/api/v1/activities?from=2026-10-08&to=2026-10-08')
            ->assertJsonCount(1, 'data.activities');

        $this->getJson('/api/v1/activities?page=2&per_page=1')
            ->assertJsonPath('data.meta.current_page', 2);
    }

    public function test_activity_update_and_delete(): void
    {
        Sanctum::actingAs($user = $this->makeUser());

        $this->postJson('/api/v1/activities', $this->payload())
            ->assertStatus(201);

        $id = Activity::where('client_id', 'act_1728380000000_ab12')
            ->value('id');

        $this->putJson('/api/v1/activities/'.$id, [
            'calories_kcal' => 500,
            'notes' => 'Great run',
        ])->assertStatus(200)
            ->assertJsonPath('data.activity.calories_kcal', 500);

        $this->deleteJson('/api/v1/activities/'.$id)
            ->assertStatus(200);

        $this->assertDatabaseCount('activities', 0);
    }

    public function test_activity_feeds_the_energy_balance(): void
    {
        Sanctum::actingAs($user = $this->makeUser());

        // BMR 1780 + everyday 979 = 2759 before any activity.
        $this->getJson('/api/v1/energy/balance?date=2026-10-08')
            ->assertStatus(200)
            ->assertJsonPath('data.consumption.sport_activity', 0.0);

        $this->postJson('/api/v1/activities', $this->payload())
            ->assertStatus(201);

        $this->getJson('/api/v1/energy/balance?date=2026-10-08')
            ->assertStatus(200)
            ->assertJsonPath('data.consumption.sport_activity', 412.0);

        $this->postJson(
            '/api/v1/activities', $this->payload([
                'client_id' => 'act_manual',
                'source' => 'manual',
                'calories_kcal' => 150,
            ])
        )->assertStatus(201);

        $this->getJson('/api/v1/energy/balance?date=2026-10-08')
            ->assertStatus(200)
            ->assertJsonPath('data.consumption.manual_activity', 150.0);

        // Deleting the tracked activity removes its calories.
        $id = Activity::where('client_id', 'act_1728380000000_ab12')
            ->value('id');

        $this->deleteJson('/api/v1/activities/'.$id)->assertStatus(200);

        $this->getJson('/api/v1/energy/balance?date=2026-10-08')
            ->assertStatus(200)
            ->assertJsonPath('data.consumption.sport_activity', 0.0);
    }

    public function test_user_cannot_touch_another_users_activity(): void
    {
        Sanctum::actingAs($this->makeUser());

        $other = $this->makeUser();
        Activity::create($this->payload() + ['user_id' => $other->id]);
        $id = Activity::where('user_id', $other->id)->value('id');

        $this->getJson('/api/v1/activities/'.$id)->assertStatus(403);
        $this->putJson('/api/v1/activities/'.$id, ['notes' => 'x'])
            ->assertStatus(403);
        $this->deleteJson('/api/v1/activities/'.$id)->assertStatus(403);
    }
}