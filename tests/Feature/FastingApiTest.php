<?php

namespace Tests\Feature;

use App\Models\Fasting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class FastingApiTest extends TestCase
{
    use RefreshDatabase;

    private function startFast(
        array $overrides = []
    ): array {
        return array_merge([
            'fasting_type' => '16:8',
            'planned_hours' => 16,
        ], $overrides);
    }

    public function test_fast_can_be_started(): void
    {
        Sanctum::actingAs($user = User::factory()->create());

        $response = $this->postJson(
            '/api/v1/fasting',
            $this->startFast()
        );

        $response
            ->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.fasting.status', 'ongoing')
            ->assertJsonPath('data.fasting.is_active', true);

        $this->assertDatabaseHas('fastings', [
            'user_id' => $user->id,
            'fasting_type' => '16:8',
            'planned_hours' => 16,
            'status' => Fasting::STATUS_ONGOING,
        ]);
    }

    public function test_second_fast_cannot_start_while_one_is_active(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->postJson(
            '/api/v1/fasting',
            $this->startFast()
        )->assertStatus(201);

        $this->postJson(
            '/api/v1/fasting',
            $this->startFast()
        )->assertStatus(422);
    }

    public function test_fast_started_in_the_past_is_marked_completed_and_builds_streak(): void
    {
        Sanctum::actingAs($user = User::factory()->create());

        $start = $this->postJson('/api/v1/fasting', $this->startFast([
            'started_at' => now()->subHours(17)->toIso8601String(),
        ]));

        $start->assertStatus(201);

        $id = $start->json('data.fasting.id');

        $end = $this->postJson("/api/v1/fasting/{$id}/end");

        $end
            ->assertStatus(200)
            ->assertJsonPath('data.fasting.status', Fasting::STATUS_COMPLETED)
            ->assertJsonPath('data.fasting.progress_percent', 100.0);

        $this->assertDatabaseHas('user_streaks', [
            'user_id' => $user->id,
            'type' => 'fasting',
            'current_streak' => 1,
        ]);
    }

    public function test_fast_ended_before_plan_is_marked_failed(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $id = $this->postJson('/api/v1/fasting', $this->startFast())
            ->json('data.fasting.id');

        $this->postJson("/api/v1/fasting/{$id}/end")
            ->assertStatus(200)
            ->assertJsonPath('data.fasting.status', Fasting::STATUS_FAILED);

        $this->assertDatabaseMissing('user_streaks', [
            'type' => 'fasting',
            'current_streak' => 1,
        ]);
    }

    public function test_fast_cannot_be_ended_twice(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $id = $this->postJson('/api/v1/fasting', $this->startFast())
            ->json('data.fasting.id');

        $this->postJson("/api/v1/fasting/{$id}/end")
            ->assertStatus(200);

        $this->postJson("/api/v1/fasting/{$id}/end")
            ->assertStatus(422);
    }

    public function test_validation_rejects_invalid_payload(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->postJson('/api/v1/fasting', [
            'fasting_type' => '16:8',
            'planned_hours' => 0,
            'started_at' => now()->addDay()->toIso8601String(),
        ])->assertStatus(422);
    }

    public function test_update_without_fields_is_rejected(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $id = $this->postJson('/api/v1/fasting', $this->startFast())
            ->json('data.fasting.id');

        $this->putJson("/api/v1/fasting/{$id}", [])
            ->assertStatus(422);

        $this->putJson("/api/v1/fasting/{$id}", [
            'notes' => 'before bed',
        ])->assertStatus(200);
    }

    public function test_user_cannot_access_another_users_fast(): void
    {
        Sanctum::actingAs($owner = User::factory()->create());

        $id = $this->postJson('/api/v1/fasting', $this->startFast())
            ->json('data.fasting.id');

        Sanctum::actingAs(User::factory()->create());

        $this->getJson("/api/v1/fasting/{$id}")->assertStatus(403);

        $this->putJson("/api/v1/fasting/{$id}", [
            'notes' => 'hacked',
        ])->assertStatus(403);

        $this->deleteJson("/api/v1/fasting/{$id}")->assertStatus(403);

        $this->getJson('/api/v1/fasting')
            ->assertStatus(200)
            ->assertJsonPath('data.fastings', []);

        $this->assertEquals(
            1,
            $owner->fastings()->count()
        );
    }

    public function test_active_and_summary_endpoints(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->getJson('/api/v1/fasting/active')
            ->assertStatus(200)
            ->assertJsonPath('data.fasting', null);

        $this->postJson('/api/v1/fasting', $this->startFast())
            ->assertStatus(201);

        $this->getJson('/api/v1/fasting/active')
            ->assertStatus(200)
            ->assertJsonPath('data.fasting.status', 'ongoing');

        $this->getJson('/api/v1/fasting/summary')
            ->assertStatus(200)
            ->assertJsonPath('data.total', 1)
            ->assertJsonPath('data.ongoing', 1)
            ->assertJsonPath('data.completed', 0);
    }

    public function test_index_filters_by_status(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $id = $this->postJson('/api/v1/fasting', $this->startFast())
            ->json('data.fasting.id');

        $this->postJson("/api/v1/fasting/{$id}/end");

        $this->getJson('/api/v1/fasting?status=failed')
            ->assertStatus(200)
            ->assertJsonCount(1, 'data.fastings')
            ->assertJsonPath('data.fastings.0.status', 'failed');

        $this->getJson('/api/v1/fasting?status=completed')
            ->assertStatus(200)
            ->assertJsonCount(0, 'data.fastings');

        $this->getJson('/api/v1/fasting?status=bogus')
            ->assertStatus(422);
    }
}
