<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\WeightLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ProfileAndWeightApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_can_be_retrieved(): void
    {
        Sanctum::actingAs($user = User::factory()->create());

        $this->getJson('/api/v1/profile')
            ->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.user.id', $user->id);
    }

    public function test_profile_can_be_updated(): void
    {
        Sanctum::actingAs($user = User::factory()->create());

        $this->putJson('/api/v1/profile', [
            'timezone' => 'Europe/Berlin',
            'activity_level' => 'high',
            'current_weight' => 81.5,
        ])
            ->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.user.profile.timezone', 'Europe/Berlin')
            ->assertJsonPath('data.user.profile.activity_level', 'high');

        $this->assertDatabaseHas('user_profiles', [
            'user_id' => $user->id,
            'timezone' => 'Europe/Berlin',
            'activity_level' => 'high',
        ]);
    }

    public function test_invalid_timezone_is_rejected(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->putJson('/api/v1/profile', [
            'timezone' => 'Not/A_Zone',
        ])->assertStatus(422);
    }

    public function test_invalid_gender_is_rejected(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->putJson('/api/v1/profile', [
            'gender' => 'zzz',
        ])->assertStatus(422)->assertJsonValidationErrors('gender');
    }

    public function test_onboarding_fields_can_be_updated(): void
    {
        Sanctum::actingAs($user = User::factory()->create());

        $this->putJson('/api/v1/profile', [
            'goal' => 'muscle_gain',
            'weight_change_pace_kg_per_week' => 0.5,
            'training_frequency' => 3,
            'training_volume' => 'moderate',
            'preferred_fasting_method' => '16:8',
            'micronutrient_focus' => ['vitamin_d', 'iron'],
            'avatar_id' => 'avatar_leaf',
            'language' => 'de',
        ])
            ->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.user.profile.goal', 'muscle_gain')
            ->assertJsonPath('data.user.profile.weight_change_pace_kg_per_week', '0.50')
            ->assertJsonPath('data.user.profile.training_frequency', 3)
            ->assertJsonPath('data.user.profile.training_volume', 'moderate')
            ->assertJsonPath('data.user.profile.preferred_fasting_method', '16:8')
            ->assertJsonPath('data.user.profile.micronutrient_focus', ['vitamin_d', 'iron'])
            ->assertJsonPath('data.user.profile.avatar_id', 'avatar_leaf')
            ->assertJsonPath('data.user.profile.language', 'de');

        $this->assertDatabaseHas('user_profiles', [
            'user_id' => $user->id,
            'goal' => 'muscle_gain',
            'weight_change_pace_kg_per_week' => 0.5,
            'training_frequency' => 3,
            'training_volume' => 'moderate',
            'preferred_fasting_method' => '16:8',
            'avatar_id' => 'avatar_leaf',
            'language' => 'de',
        ]);
    }

    public function test_onboarding_field_validation(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->putJson('/api/v1/profile', [
            'goal' => 'lose_fast',
            'weight_change_pace_kg_per_week' => 2.0,
            'training_frequency' => 20,
            'training_volume' => 'extreme',
            'micronutrient_focus' => [12],
            'language' => 'fr',
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors([
                'goal',
                'weight_change_pace_kg_per_week',
                'training_frequency',
                'training_volume',
                'micronutrient_focus.0',
                'language',
            ]);
    }

    public function test_date_of_birth_is_serialized_consistently(): void
    {
        Sanctum::actingAs($user = User::factory()->create());

        $put = $this->putJson('/api/v1/profile', [
            'date_of_birth' => '1990-05-01',
            'gender' => 'female',
        ]);

        $put->assertStatus(200)
            ->assertJsonPath('data.user.profile.date_of_birth', '1990-05-01');

        $this->getJson('/api/v1/profile')
            ->assertStatus(200)
            ->assertJsonPath('data.user.profile.date_of_birth', '1990-05-01');
    }

    public function test_empty_profile_update_is_rejected(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->putJson('/api/v1/profile', [])
            ->assertStatus(422)
            ->assertJsonPath('message', 'No updatable fields received.');
    }

    public function test_weight_log_is_stored_in_the_users_timezone(): void
    {
        Sanctum::actingAs($user = User::factory()->create());

        $user->profile()->create([
            'first_name' => 'Test',
            'last_name' => 'User',
            'timezone' => 'Pacific/Auckland',
        ]);

        $this->postJson('/api/v1/weight-logs', [
            'weight' => 78.4,
            'logged_at' => '2026-10-07T20:00:00Z',
        ])
            ->assertStatus(201)
            ->assertJsonPath('data.weight_log.weight', '78.40')
            ->assertJsonPath('data.weight_log.date', '2026-10-08');

        $this->assertNotNull(
            WeightLog::where('user_id', $user->id)
                ->whereDate('date', '2026-10-08')
                ->where('source', WeightLog::SOURCE_MANUAL)
                ->first()
        );
    }

    public function test_logging_weight_syncs_the_profile_and_latest_endpoint(): void
    {
        Sanctum::actingAs($user = User::factory()->create());

        $user->profile()->create([
            'first_name' => 'Test',
            'last_name' => 'User',
            'current_weight' => 90,
            'weight_unit' => 'kg',
        ]);

        $this->postJson('/api/v1/weight-logs', [
            'weight' => 77.2,
            'logged_at' => now()->subDay()->toIso8601String(),
        ])->assertStatus(201);

        $this->postJson('/api/v1/weight-logs', [
            'weight' => 76.8,
        ])->assertStatus(201);

        $this->getJson('/api/v1/weight-logs/latest')
            ->assertStatus(200)
            ->assertJsonPath('data.weight_log.weight', '76.80');

        $this->assertDatabaseHas('user_profiles', [
            'user_id' => $user->id,
            'current_weight' => 76.8,
        ]);
    }

    public function test_deleting_the_latest_log_restores_the_previous_profile_weight(): void
    {
        Sanctum::actingAs($user = User::factory()->create());

        $user->profile()->create([
            'first_name' => 'Test',
            'last_name' => 'User',
        ]);

        $first = $this->postJson('/api/v1/weight-logs', [
            'weight' => 80,
            'logged_at' => now()->subDays(2)->toIso8601String(),
        ])->json('data.weight_log.id');

        $second = $this->postJson('/api/v1/weight-logs', [
            'weight' => 79,
            'logged_at' => now()->subDay()->toIso8601String(),
        ])->json('data.weight_log.id');

        $this->deleteJson('/api/v1/weight-logs/'.$second)
            ->assertStatus(200);

        $this->assertDatabaseHas('user_profiles', [
            'user_id' => $user->id,
            'current_weight' => 80,
        ]);

        $this->assertDatabaseMissing('weight_logs', ['id' => $second]);
        $this->assertDatabaseHas('weight_logs', ['id' => $first]);
    }

    public function test_weight_logs_can_be_filtered_by_period(): void
    {
        Sanctum::actingAs($user = User::factory()->create());

        $this->postJson('/api/v1/weight-logs', [
            'weight' => 80,
            'logged_at' => '2026-01-10T09:00:00Z',
        ])->assertStatus(201);

        $this->postJson('/api/v1/weight-logs', [
            'weight' => 79,
            'logged_at' => '2026-03-05T09:00:00Z',
        ])->assertStatus(201);

        $this->getJson('/api/v1/weight-logs?from=2026-03-01&to=2026-03-31')
            ->assertStatus(200)
            ->assertJsonCount(1, 'data.weight_logs')
            ->assertJsonPath('data.weight_logs.0.weight', '79.00');
    }

    public function test_user_cannot_access_another_users_weight_log(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $other = User::factory()->create();
        $log = $other->weightLogs()->create([
            'weight' => 70,
            'unit' => 'kg',
            'logged_at' => now(),
            'date' => now()->toDateString(),
            'source' => 'manual',
        ]);

        $this->getJson('/api/v1/weight-logs/'.$log->id)
            ->assertStatus(403);

        $this->putJson('/api/v1/weight-logs/'.$log->id, [
            'weight' => 71,
        ])->assertStatus(403);

        $this->deleteJson('/api/v1/weight-logs/'.$log->id)
            ->assertStatus(403);
    }
}
