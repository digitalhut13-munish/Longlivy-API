<?php

namespace Tests\Feature;

use App\Models\Meditation;
use App\Models\MeditationSession;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class MeditationSessionApiTest extends TestCase
{
    use RefreshDatabase;

    private function makeMeditation(
        array $overrides = []
    ): Meditation {
        return Meditation::create(array_merge([
            'type' => 'guided',
            'title' => 'Calm Body Scan',
            'duration_minutes' => 10,
            'status' => Meditation::STATUS_PUBLISHED,
            'released_at' => now()->subDay(),
        ], $overrides));
    }

    private function makeSession(
        User $user,
        array $overrides = []
    ): MeditationSession {
        return $user->meditationSessions()->create(array_merge([
            'type' => 'free',
            'status' => MeditationSession::STATUS_ACTIVE,
            'planned_minutes' => 10,
            'started_at' => now(),
            'date' => now()->toDateString(),
        ], $overrides));
    }

    private function startPayload(array $overrides = []): array
    {
        return array_merge([
            'type' => 'free',
            'planned_minutes' => 10,
        ], $overrides);
    }

    public function test_session_can_be_started_and_returned_as_active(): void
    {
        Sanctum::actingAs($user = User::factory()->create());

        $response = $this->postJson(
            '/api/v1/meditation/sessions',
            $this->startPayload()
        )
            ->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.session.status', 'active')
            ->assertJsonPath('data.session.type', 'free')
            ->assertJsonPath('data.session.is_running', true);

        $id = $response->json('data.session.id');

        $this->assertDatabaseHas('meditation_sessions', [
            'id' => $id,
            'user_id' => $user->id,
            'status' => MeditationSession::STATUS_ACTIVE,
        ]);

        $this->getJson('/api/v1/meditation/sessions/active')
            ->assertStatus(200)
            ->assertJsonPath('data.session.id', $id);
    }

    public function test_second_session_cannot_start_while_one_is_running(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->postJson(
            '/api/v1/meditation/sessions',
            $this->startPayload()
        )->assertStatus(201);

        $this->postJson(
            '/api/v1/meditation/sessions',
            $this->startPayload()
        )->assertStatus(422);
    }

    public function test_start_validates_duration_type_and_meditation(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->postJson('/api/v1/meditation/sessions', [
            'planned_minutes' => 10,
        ])->assertStatus(422)
            ->assertJsonPath('errors.type.0', 'Meditation type is required.');

        $this->postJson(
            '/api/v1/meditation/sessions',
            $this->startPayload(['planned_minutes' => 500])
        )->assertStatus(422);

        $this->postJson(
            '/api/v1/meditation/sessions',
            $this->startPayload(['type' => 'medical'])
        )->assertStatus(422);

        $this->postJson('/api/v1/meditation/sessions', [
            'meditation_id' => 999999,
            'planned_minutes' => 10,
        ])->assertStatus(422);

        $draft = $this->makeMeditation([
            'status' => Meditation::STATUS_DESIGN,
        ]);

        $this->postJson('/api/v1/meditation/sessions', [
            'meditation_id' => $draft->id,
            'planned_minutes' => 10,
        ])
            ->assertStatus(422)
            ->assertJsonPath(
                'errors.meditation_id.0',
                'The selected meditation is not available.'
            );
    }

    public function test_session_started_from_meditation_defaults_to_its_type(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $meditation = $this->makeMeditation();

        $this->postJson('/api/v1/meditation/sessions', [
            'meditation_id' => $meditation->id,
            'planned_minutes' => 10,
        ])
            ->assertStatus(201)
            ->assertJsonPath('data.session.type', 'guided')
            ->assertJsonPath('data.session.meditation.id', $meditation->id);
    }

    public function test_pause_resume_and_complete_excludes_paused_time(): void
    {
        Sanctum::actingAs($user = User::factory()->create());

        $start = now()->subMinutes(30);

        $session = $this->makeSession($user, [
            'status' => MeditationSession::STATUS_PAUSED,
            'started_at' => $start,
            'paused_at' => $start->copy()->addMinutes(4),
            'paused_seconds' => 60,
            'date' => $start->toDateString(),
        ]);

        $response = $this->postJson(
            "/api/v1/meditation/sessions/{$session->id}/complete",
            [
                'ended_at' => $start
                    ->copy()
                    ->addMinutes(10)
                    ->toIso8601String(),
            ]
        )
            ->assertStatus(200)
            ->assertJsonPath('data.session.status', 'completed')
            ->assertJsonPath('data.session.actual_minutes', 3);

        $this->assertDatabaseHas('meditation_sessions', [
            'id' => $session->id,
            'status' => MeditationSession::STATUS_COMPLETED,
            'actual_minutes' => 3,
        ]);

        $this->assertSame(
            3,
            $response->json('data.session.actual_minutes')
        );
    }

    public function test_pause_resume_complete_flow_through_endpoints(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $id = $this->postJson('/api/v1/meditation/sessions', [
            'type' => 'free',
            'planned_minutes' => 10,
            'started_at' => now()->subMinutes(10)->toIso8601String(),
        ])->assertStatus(201)->json('data.session.id');

        $this->postJson("/api/v1/meditation/sessions/{$id}/pause")
            ->assertStatus(200)
            ->assertJsonPath('data.session.status', 'paused');

        $this->postJson("/api/v1/meditation/sessions/{$id}/pause")
            ->assertStatus(422);

        $this->postJson("/api/v1/meditation/sessions/{$id}/resume")
            ->assertStatus(200)
            ->assertJsonPath('data.session.status', 'active');

        $this->postJson("/api/v1/meditation/sessions/{$id}/resume")
            ->assertStatus(422);

        $session = $this->postJson(
            "/api/v1/meditation/sessions/{$id}/complete"
        )
            ->assertStatus(200)
            ->assertJsonPath('data.session.status', 'completed')
            ->json('data.session');

        $this->assertGreaterThanOrEqual(
            9,
            $session['actual_minutes']
        );
    }

    public function test_complete_builds_streak_and_updates_goal_progress(): void
    {
        Sanctum::actingAs($user = User::factory()->create());

        $goal = $user->goals()->create([
            'goal_type' => 'meditation',
            'name' => 'Meditate 30 minutes daily',
            'target_value' => 30,
            'unit' => 'minutes',
            'period' => 'day',
            'start_date' => now()->toDateString(),
        ]);

        $id = $this->postJson('/api/v1/meditation/sessions', [
            'type' => 'free',
            'planned_minutes' => 10,
            'started_at' => now()->subMinutes(10)->toIso8601String(),
        ])->assertStatus(201)->json('data.session.id');

        $this->postJson("/api/v1/meditation/sessions/{$id}/complete")
            ->assertStatus(200);

        $this->assertDatabaseHas('user_streaks', [
            'user_id' => $user->id,
            'type' => 'meditation',
            'current_streak' => 1,
        ]);

        $progress = $goal->progress()->get();

        $this->assertCount(1, $progress);
        $this->assertGreaterThanOrEqual(10, (float) $progress[0]->value);
        $this->assertFalse($progress[0]->completed);

        $second = $this->postJson('/api/v1/meditation/sessions', [
            'type' => 'free',
            'planned_minutes' => 10,
            'started_at' => now()->subMinutes(5)->toIso8601String(),
        ])->json('data.session.id');

        $this->postJson("/api/v1/meditation/sessions/{$second}/complete")
            ->assertStatus(200);

        $progress = $goal->progress()->get();

        $this->assertGreaterThan(
            10,
            (float) $progress->first()->value
        );
    }

    public function test_complete_below_streak_threshold_does_not_count(): void
    {
        config(['longlivy.meditation.min_streak_minutes' => 30]);

        Sanctum::actingAs($user = User::factory()->create());

        $id = $this->postJson('/api/v1/meditation/sessions', [
            'type' => 'free',
            'planned_minutes' => 10,
            'started_at' => now()->subMinutes(10)->toIso8601String(),
        ])->json('data.session.id');

        $this->postJson("/api/v1/meditation/sessions/{$id}/complete")
            ->assertStatus(200)
            ->assertJsonPath('data.session.status', 'completed');

        $this->assertDatabaseMissing('user_streaks', [
            'user_id' => $user->id,
            'type' => 'meditation',
        ]);
    }

    public function test_session_goal_progress_can_be_counted_in_sessions(): void
    {
        Sanctum::actingAs($user = User::factory()->create());

        $goal = $user->goals()->create([
            'goal_type' => 'meditation',
            'name' => 'Five sessions a week',
            'target_value' => 5,
            'unit' => 'sessions',
            'period' => 'week',
            'start_date' => now()->toDateString(),
        ]);

        $id = $this->postJson('/api/v1/meditation/sessions', [
            'type' => 'free',
            'planned_minutes' => 3,
            'started_at' => now()->subMinutes(3)->toIso8601String(),
        ])->json('data.session.id');

        $this->postJson("/api/v1/meditation/sessions/{$id}/complete")
            ->assertStatus(200);

        $this->assertDatabaseHas('goal_progress', [
            'goal_id' => $goal->id,
            'value' => 1,
            'completed' => false,
        ]);
    }

    public function test_cancelled_session_counts_nothing_and_cannot_run_again(): void
    {
        Sanctum::actingAs($user = User::factory()->create());

        $id = $this->postJson(
            '/api/v1/meditation/sessions',
            $this->startPayload()
        )->json('data.session.id');

        $this->postJson("/api/v1/meditation/sessions/{$id}/cancel")
            ->assertStatus(200)
            ->assertJsonPath('data.session.status', 'cancelled');

        $this->assertDatabaseMissing('user_streaks', [
            'user_id' => $user->id,
            'type' => 'meditation',
        ]);

        $this->postJson("/api/v1/meditation/sessions/{$id}/complete")
            ->assertStatus(422);

        $this->postJson("/api/v1/meditation/sessions/{$id}/pause")
            ->assertStatus(422);
    }

    public function test_completed_session_cannot_be_completed_twice(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $id = $this->postJson(
            '/api/v1/meditation/sessions',
            $this->startPayload()
        )->json('data.session.id');

        $this->postJson("/api/v1/meditation/sessions/{$id}/complete")
            ->assertStatus(200);

        $this->postJson("/api/v1/meditation/sessions/{$id}/complete")
            ->assertStatus(422);
    }

    public function test_history_filters_notes_update_and_delete(): void
    {
        Sanctum::actingAs($user = User::factory()->create());

        $completed = $this->makeSession($user, [
            'status' => MeditationSession::STATUS_COMPLETED,
            'type' => 'guided',
            'actual_minutes' => 10,
            'ended_at' => now(),
        ]);
        $this->makeSession($user, [
            'status' => MeditationSession::STATUS_CANCELLED,
            'type' => 'free',
            'started_at' => now()->subDays(2),
            'date' => now()->subDays(2)->toDateString(),
        ]);

        $history = $this->getJson(
            '/api/v1/meditation/sessions?status=completed'
        )->json('data.sessions');

        $this->assertCount(1, $history);
        $this->assertSame($completed->id, $history[0]['id']);

        $byType = $this->getJson(
            '/api/v1/meditation/sessions?type=free'
        )->json('data.sessions');

        $this->assertCount(1, $byType);

        $byDate = $this->getJson(
            '/api/v1/meditation/sessions?from='
            .now()->subDay()->toDateString()
            .'&to='.now()->toDateString()
        )->json('data.sessions');

        $this->assertCount(1, $byDate);

        $this->putJson(
            "/api/v1/meditation/sessions/{$completed->id}",
            ['notes' => 'Felt calm.']
        )
            ->assertStatus(200)
            ->assertJsonPath('data.session.notes', 'Felt calm.');

        $this->putJson(
            "/api/v1/meditation/sessions/{$completed->id}",
            []
        )->assertStatus(422);

        $this->getJson("/api/v1/meditation/sessions/{$completed->id}")
            ->assertStatus(200)
            ->assertJsonPath('data.session.id', $completed->id);

        $this->deleteJson(
            "/api/v1/meditation/sessions/{$completed->id}"
        )->assertStatus(200);

        $this->assertDatabaseMissing('meditation_sessions', [
            'id' => $completed->id,
        ]);
    }

    public function test_sessions_are_scoped_to_their_owner(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();

        $session = $this->makeSession($owner);

        Sanctum::actingAs($other);

        $this->getJson("/api/v1/meditation/sessions/{$session->id}")
            ->assertStatus(403);

        $this->postJson(
            "/api/v1/meditation/sessions/{$session->id}/complete"
        )->assertStatus(403);

        $this->deleteJson("/api/v1/meditation/sessions/{$session->id}")
            ->assertStatus(403);
    }

    public function test_meditation_goals_are_typed_and_scoped(): void
    {
        Sanctum::actingAs($user = User::factory()->create());

        $goal = $this->postJson('/api/v1/meditation/goals', [
            'name' => 'Meditate daily',
            'target_value' => 5,
            'unit' => 'sessions',
            'period' => 'day',
            'start_date' => now()->toDateString(),
        ])
            ->assertStatus(201)
            ->assertJsonPath('data.goal.goal_type', 'meditation')
            ->json('data.goal');

        $otherGoal = $user->goals()->create([
            'goal_type' => 'weight',
            'name' => 'Lose weight',
            'target_value' => 5,
            'unit' => 'kg',
            'period' => 'month',
            'start_date' => now()->toDateString(),
        ]);

        $goals = $this->getJson('/api/v1/meditation/goals')
            ->json('data.goals');

        $this->assertCount(1, $goals);
        $this->assertSame($goal['id'], $goals[0]['id']);

        $this->getJson("/api/v1/meditation/goals/{$otherGoal->id}")
            ->assertStatus(404);

        $this->putJson("/api/v1/meditation/goals/{$goal['id']}", [
            'target_value' => 7,
        ])
            ->assertStatus(200)
            ->assertJsonPath('data.goal.target.value', '7.00');

        $this->postJson(
            "/api/v1/meditation/goals/{$goal['id']}/progress",
            [
                'date' => now()->toDateString(),
                'value' => 7,
            ]
        )
            ->assertStatus(201)
            ->assertJsonPath('data.progress.completed', true);

        $progress = $this->getJson(
            "/api/v1/meditation/goals/{$goal['id']}/progress"
        )->json('data.progress');

        $this->assertCount(1, $progress);

        $this->deleteJson("/api/v1/meditation/goals/{$goal['id']}")
            ->assertStatus(200);

        $this->assertDatabaseMissing('goals', [
            'id' => $goal['id'],
        ]);
    }

    public function test_meditation_goals_cannot_touch_other_users_goals(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();

        $goal = $owner->goals()->create([
            'goal_type' => 'meditation',
            'name' => 'Owner goal',
            'target_value' => 5,
            'unit' => 'sessions',
            'period' => 'week',
            'start_date' => now()->toDateString(),
        ]);

        Sanctum::actingAs($other);

        $this->getJson("/api/v1/meditation/goals/{$goal->id}")
            ->assertStatus(404);

        $this->putJson("/api/v1/meditation/goals/{$goal->id}", [
            'target_value' => 99,
        ])->assertStatus(404);

        $this->deleteJson("/api/v1/meditation/goals/{$goal->id}")
            ->assertStatus(404);
    }

    public function test_reminders_can_be_managed(): void
    {
        Sanctum::actingAs($user = User::factory()->create());

        $reminder = $this->postJson('/api/v1/meditation/reminders', [
            'time' => '07:30',
            'days_of_week' => '1,2,3,4,5',
            'label' => 'Morning sit',
        ])
            ->assertStatus(201)
            ->assertJsonPath('data.reminder.time', '07:30')
            ->assertJsonPath('data.reminder.enabled', true)
            ->assertJsonPath('data.reminder.days.0', 1)
            ->json('data.reminder');

        $list = $this->getJson('/api/v1/meditation/reminders')
            ->json('data.reminders');

        $this->assertCount(1, $list);

        $this->putJson(
            "/api/v1/meditation/reminders/{$reminder['id']}",
            ['enabled' => false]
        )
            ->assertStatus(200)
            ->assertJsonPath('data.reminder.enabled', false);

        $this->putJson(
            "/api/v1/meditation/reminders/{$reminder['id']}",
            []
        )->assertStatus(422);

        $this->deleteJson(
            "/api/v1/meditation/reminders/{$reminder['id']}"
        )->assertStatus(200);

        $this->assertDatabaseMissing('meditation_reminders', [
            'user_id' => $user->id,
        ]);
    }

    public function test_reminder_validation_rejects_bad_input(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->postJson('/api/v1/meditation/reminders', [
            'time' => '25:99',
        ])->assertStatus(422);

        $this->postJson('/api/v1/meditation/reminders', [
            'time' => '07:30',
            'days_of_week' => '8,9',
        ])->assertStatus(422);

        $this->postJson('/api/v1/meditation/reminders', [
            'days_of_week' => '1,2,3',
        ])->assertStatus(422);
    }

    public function test_reminders_are_scoped_to_their_owner(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();

        $reminder = $owner->meditationReminders()->create([
            'time' => '08:00',
            'days_of_week' => '0,1,2,3,4,5,6',
        ]);

        Sanctum::actingAs($other);

        $this->putJson(
            "/api/v1/meditation/reminders/{$reminder->id}",
            ['enabled' => false]
        )->assertStatus(403);

        $this->deleteJson(
            "/api/v1/meditation/reminders/{$reminder->id}"
        )->assertStatus(403);
    }
}
