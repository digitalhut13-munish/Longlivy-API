<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class FastingPlanApiTest extends TestCase
{
    use RefreshDatabase;

    private function planPayload(array $overrides = []): array
    {
        return array_merge([
            'method' => '16:8',
            'category' => 'intermittent',
            'recurring' => true,
            'start_time' => '20:00',
            'end_time' => '12:00',
            'weekdays' => [1, 2, 3, 4, 5],
            'start_date' => '2026-10-19',
            'timezone' => 'Asia/Kolkata',
            'fasting_hours' => 16,
            'eating_hours' => 8,
            'active' => true,
        ], $overrides);
    }

    private function createPlan(array $overrides = []): int
    {
        return $this->postJson(
            '/api/v1/fasting/plans',
            $this->planPayload($overrides)
        )
            ->assertStatus(201)
            ->json('data.plan.id');
    }

    public function test_plan_is_created_and_weekdays_round_trip_unchanged(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $id = $this->createPlan();

        $this->getJson("/api/v1/fasting/plans/{$id}")
            ->assertStatus(200)
            ->assertJsonPath('data.plan.weekdays', [1, 2, 3, 4, 5]);

        $this->getJson('/api/v1/fasting/plans')
            ->assertStatus(200)
            ->assertJsonPath('data.plans.0.weekdays', [1, 2, 3, 4, 5]);
    }

    public function test_plan_list_and_show_do_not_error_on_overrides(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $id = $this->createPlan();

        $this->getJson('/api/v1/fasting/plans')
            ->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->getJson("/api/v1/fasting/plans/{$id}")
            ->assertStatus(200)
            ->assertJsonPath('data.plan.method', '16:8')
            ->assertJsonPath('data.plan.timezone', 'Asia/Kolkata');
    }

    public function test_plan_can_be_updated_with_partial_payload(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $id = $this->createPlan();

        $this->putJson("/api/v1/fasting/plans/{$id}", ['active' => false])
            ->assertStatus(200)
            ->assertJsonPath('data.plan.active', false)
            ->assertJsonPath('data.plan.fasting_hours', 16)
            ->assertJsonPath('data.plan.weekdays', [1, 2, 3, 4, 5]);
    }

    public function test_plan_can_be_deleted(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $id = $this->createPlan();

        $this->deleteJson("/api/v1/fasting/plans/{$id}")
            ->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->getJson("/api/v1/fasting/plans/{$id}")->assertStatus(404);
    }

    public function test_per_day_overrides_can_be_listed_saved_and_removed(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $id = $this->createPlan();

        $this->putJson(
            "/api/v1/fasting/plans/{$id}/overrides/2026-10-20",
            ['action' => 'skip']
        )
            ->assertStatus(200)
            ->assertJsonPath('data.override.action', 'skip')
            ->assertJsonPath('data.override.date', '2026-10-20');

        $this->getJson("/api/v1/fasting/plans/{$id}/overrides")
            ->assertStatus(200)
            ->assertJsonPath('data.overrides.0.action', 'skip');

        $this->deleteJson("/api/v1/fasting/plans/{$id}/overrides/2026-10-20")
            ->assertStatus(200);

        $this->getJson("/api/v1/fasting/plans/{$id}/overrides")
            ->assertStatus(200)
            ->assertJsonCount(0, 'data.overrides');
    }

    public function test_reschedule_override_requires_both_times(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $id = $this->createPlan();

        $this->putJson(
            "/api/v1/fasting/plans/{$id}/overrides/2026-10-20",
            ['action' => 'reschedule']
        )->assertStatus(422);
    }

    public function test_unknown_method_is_rejected(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->postJson(
            '/api/v1/fasting/plans',
            $this->planPayload(['method' => 'custom'])
        )
            ->assertStatus(422)
            ->assertJsonPath(
                'message',
                'The selected fasting method is invalid.'
            );
    }

    public function test_database_errors_do_not_leak_sql_to_clients(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->createPlan();

        Schema::drop('fasting_plan_overrides');

        $response = $this->getJson('/api/v1/fasting/plans');

        $response
            ->assertStatus(500)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Server error.');

        $this->assertStringNotContainsString(
            'SQLSTATE',
            $response->getContent()
        );
    }

    public function test_cannot_touch_another_users_plan(): void
    {
        Sanctum::actingAs($owner = User::factory()->create());

        $id = $this->createPlan();

        Sanctum::actingAs($other = User::factory()->create());

        $this->getJson("/api/v1/fasting/plans/{$id}")
            ->assertStatus(403);

        $this->deleteJson("/api/v1/fasting/plans/{$id}")
            ->assertStatus(403);

        unset($owner, $other);
    }
}
