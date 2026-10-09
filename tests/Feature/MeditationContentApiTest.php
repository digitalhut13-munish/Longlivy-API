<?php

namespace Tests\Feature;

use App\Models\Meditation;
use App\Models\MeditationSession;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class MeditationContentApiTest extends TestCase
{
    use RefreshDatabase;

    private function makePublishedMeditation(
        array $overrides = [],
        array $audio = [['language' => 'en']]
    ): Meditation {
        $meditation = Meditation::create(array_merge([
            'type' => 'guided',
            'title' => 'Deep Relaxation',
            'description' => 'Full-body relaxation.',
            'duration_minutes' => 15,
            'duration_seconds' => 900,
            'thumbnail_path' => 'https://cdn.longlivy.example/meditations/deep-relaxation/thumb@2x.jpg',
            'status' => Meditation::STATUS_PUBLISHED,
            'released_at' => now()->subDay(),
            'language' => 'en',
            'availability' => Meditation::AVAILABILITY_AVAILABLE,
        ], $overrides));

        foreach ($audio as $row) {
            $meditation->audio()->create(array_merge([
                'storage_path' => "https://audio.longlivy.example/meditations/deep-relaxation_{$row['language']}.mp3",
                'format' => 'mp3',
                'mime_type' => 'audio/mpeg',
                'bitrate_kbps' => 128,
                'duration_seconds' => 900,
                'size_bytes' => 14400000,
            ], $row));
        }

        return $meditation;
    }

    private function makeRunningSession(
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

    public function test_catalog_publishes_content_audio_and_paging_meta(): void
    {
        $this->makePublishedMeditation();
        $this->makePublishedMeditation([
            'title' => 'Hidden Draft',
            'status' => Meditation::STATUS_DESIGN,
        ]);

        Sanctum::actingAs(User::factory()->create());

        $response = $this->getJson('/api/v1/meditation')
            ->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.meta.current_page', 1)
            ->assertJsonPath('data.meta.per_page', 50)
            ->assertJsonPath('data.meta.total', 1)
            ->assertJsonPath('data.meta.last_page', 1);

        $item = $response->json('data.meditations.0');

        $this->assertSame(900, $item['duration_seconds']);
        $this->assertSame('available', $item['availability']);
        $this->assertFalse($item['is_premium']);
        $this->assertFalse($item['is_favorite']);
        $this->assertNull($item['sound_category']);
        $this->assertSame(
            'https://cdn.longlivy.example/meditations/deep-relaxation/thumb@2x.jpg',
            $item['thumbnail_url']
        );

        $this->assertNotNull($item['audio']);
        $this->assertTrue(str_ends_with(
            $item['audio']['url'],
            '_en.mp3'
        ));
        $this->assertSame('audio/mpeg', $item['audio']['mime_type']);
        $this->assertSame(128, $item['audio']['bitrate_kbps']);
        $this->assertSame(900, $item['audio']['duration_seconds']);
        $this->assertSame(14400000, $item['audio']['size_bytes']);
        $this->assertTrue($item['audio']['is_streamable']);
        $this->assertNull($item['audio']['expires_at']);
    }

    public function test_audio_is_selected_per_requested_language(): void
    {
        $this->makePublishedMeditation(
            [],
            [['language' => 'en'], ['language' => 'de']]
        );

        Sanctum::actingAs(User::factory()->create());

        $catalog = $this->getJson('/api/v1/meditation')
            ->json('data.meditations.0');

        $this->assertTrue(str_ends_with(
            $catalog['audio']['url'],
            '_en.mp3'
        ));

        $detail = $this->getJson(
            '/api/v1/meditation/'.$catalog['id'].'?language=de'
        )->json('data.meditation');

        $this->assertTrue(str_ends_with(
            $detail['audio']['url'],
            '_de.mp3'
        ));
    }

    public function test_detail_returns_404_message_for_unknown_item(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->getJson('/api/v1/meditation/999999')
            ->assertStatus(404)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Meditation not found.');
    }

    public function test_breathing_items_carry_a_breathing_pattern(): void
    {
        $this->makePublishedMeditation([
            'type' => 'breathing',
            'title' => 'Box Breathing',
            'breathing_pattern' => [
                'inhale_seconds' => 4,
                'hold_seconds' => 4,
                'exhale_seconds' => 4,
                'second_hold_seconds' => 4,
                'repetitions' => 8,
            ],
        ]);

        Sanctum::actingAs(User::factory()->create());

        $this->getJson('/api/v1/meditation')
            ->assertStatus(200)
            ->assertJsonPath(
                'data.meditations.0.breathing_pattern.inhale_seconds',
                4
            )
            ->assertJsonPath(
                'data.meditations.0.breathing_pattern.repetitions',
                8
            );
    }

    public function test_is_favorite_is_reported_for_the_current_user(): void
    {
        $meditation = $this->makePublishedMeditation();

        Sanctum::actingAs($user = User::factory()->create());

        $this->postJson('/api/v1/favorites', [
            'type' => 'meditation',
            'id' => $meditation->id,
        ])->assertStatus(201);

        $this->getJson('/api/v1/meditation')
            ->assertStatus(200)
            ->assertJsonPath('data.meditations.0.is_favorite', true);
    }

    public function test_playback_progress_is_saved_and_returned(): void
    {
        Sanctum::actingAs($user = User::factory()->create());

        $session = $this->makeRunningSession($user);

        $this->putJson(
            "/api/v1/meditation/sessions/{$session->id}",
            [
                'position_seconds' => 312,
                'active_seconds' => 300,
                'paused_seconds' => 45,
                'client_timestamp' => '2026-10-08T11:31:12+00:00',
            ]
        )
            ->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.session.position_seconds', 312)
            ->assertJsonPath('data.session.active_seconds', 300)
            ->assertJsonPath('data.session.paused_seconds', 45);

        $this->assertDatabaseHas('meditation_sessions', [
            'id' => $session->id,
            'position_seconds' => 312,
            'active_seconds' => 300,
            'paused_seconds' => 45,
        ]);

        $this->getJson('/api/v1/meditation/sessions/active')
            ->assertStatus(200)
            ->assertJsonPath('data.session.position_seconds', 312)
            ->assertJsonPath('data.session.active_seconds', 300)
            ->assertJsonPath('data.session.paused_seconds', 45);
    }

    public function test_older_progress_updates_are_ignored(): void
    {
        Sanctum::actingAs($user = User::factory()->create());

        $session = $this->makeRunningSession($user);

        $this->putJson(
            "/api/v1/meditation/sessions/{$session->id}",
            [
                'position_seconds' => 312,
                'active_seconds' => 300,
                'paused_seconds' => 45,
                'client_timestamp' => '2026-10-08T11:31:12+00:00',
            ]
        )->assertStatus(200);

        $this->putJson(
            "/api/v1/meditation/sessions/{$session->id}",
            [
                'position_seconds' => 50,
                'active_seconds' => 40,
                'paused_seconds' => 5,
                'client_timestamp' => '2026-10-08T11:20:00+00:00',
            ]
        )
            ->assertStatus(200)
            ->assertJsonPath('data.session.position_seconds', 312);

        $this->assertDatabaseHas('meditation_sessions', [
            'id' => $session->id,
            'position_seconds' => 312,
            'active_seconds' => 300,
        ]);
    }

    public function test_progress_requires_the_full_client_payload(): void
    {
        Sanctum::actingAs($user = User::factory()->create());

        $session = $this->makeRunningSession($user);

        $this->putJson(
            "/api/v1/meditation/sessions/{$session->id}",
            ['client_timestamp' => '2026-10-08T11:31:12+00:00']
        )
            ->assertStatus(422)
            ->assertJsonValidationErrors([
                'position_seconds',
                'active_seconds',
                'paused_seconds',
            ]);
    }

    public function test_progress_on_a_non_running_session_is_rejected(): void
    {
        Sanctum::actingAs($user = User::factory()->create());

        $session = $this->makeRunningSession($user, [
            'status' => MeditationSession::STATUS_COMPLETED,
            'actual_minutes' => 10,
            'ended_at' => now(),
        ]);

        $this->putJson(
            "/api/v1/meditation/sessions/{$session->id}",
            [
                'position_seconds' => 100,
                'active_seconds' => 90,
                'paused_seconds' => 10,
                'client_timestamp' => '2026-10-08T11:31:12+00:00',
            ]
        )
            ->assertStatus(422)
            ->assertJsonPath(
                'message',
                'This meditation session is not running.'
            )
            ->assertJsonPath(
                'errors.session.0',
                'This meditation session is not running.'
            );
    }

    public function test_progress_is_rate_limited_per_session(): void
    {
        Sanctum::actingAs($user = User::factory()->create());

        $session = $this->makeRunningSession($user);

        for ($i = 0; $i < 10; $i++) {
            $this->putJson(
                "/api/v1/meditation/sessions/{$session->id}",
                [
                    'position_seconds' => $i * 10,
                    'active_seconds' => $i * 10,
                    'paused_seconds' => 0,
                    'client_timestamp' => '2026-10-08T11:'.str_pad(
                        (string) (30 + $i),
                        2,
                        '0',
                        STR_PAD_LEFT
                    ).':12+00:00',
                ]
            )->assertStatus(200);
        }

        $this->putJson(
            "/api/v1/meditation/sessions/{$session->id}",
            [
                'position_seconds' => 999,
                'active_seconds' => 999,
                'paused_seconds' => 0,
                'client_timestamp' => '2026-10-08T11:59:12+00:00',
            ]
        )->assertStatus(429);
    }

    public function test_complete_accepts_client_durations(): void
    {
        Sanctum::actingAs($user = User::factory()->create());

        $session = $this->makeRunningSession($user, [
            'started_at' => now()->subMinutes(10),
        ]);

        $this->postJson(
            "/api/v1/meditation/sessions/{$session->id}/complete",
            [
                'active_seconds' => 301,
                'paused_seconds' => 5,
            ]
        )
            ->assertStatus(200)
            ->assertJsonPath('data.session.status', 'completed')
            ->assertJsonPath('data.session.actual_minutes', 5)
            ->assertJsonPath('data.session.active_seconds', 301)
            ->assertJsonPath('data.session.paused_seconds', 5);

        $this->assertDatabaseHas('meditation_sessions', [
            'id' => $session->id,
            'actual_minutes' => 5,
            'active_seconds' => 301,
            'paused_seconds' => 5,
        ]);
    }

    public function test_complete_rejects_active_seconds_beyond_elapsed_time(): void
    {
        Sanctum::actingAs($user = User::factory()->create());

        $session = $this->makeRunningSession($user, [
            'started_at' => now()->subMinutes(5),
        ]);

        $this->postJson(
            "/api/v1/meditation/sessions/{$session->id}/complete",
            ['active_seconds' => 900]
        )
            ->assertStatus(422)
            ->assertJsonValidationErrors(['active_seconds']);
    }

    public function test_templates_can_be_cruded(): void
    {
        Sanctum::actingAs($user = User::factory()->create());

        $template = $this->postJson('/api/v1/meditation/templates', [
            'name' => 'Morning 10',
            'duration_seconds' => 600,
            'type' => 'free',
            'breathing_enabled' => false,
            'closing_sound_enabled' => true,
            'background_sound' => 'ambient_rain_soft',
        ])
            ->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Template saved.')
            ->assertJsonPath('data.template.name', 'Morning 10')
            ->assertJsonPath('data.template.duration_seconds', 600)
            ->assertJsonPath('data.template.type', 'free')
            ->assertJsonPath('data.template.breathing_enabled', false)
            ->assertJsonPath('data.template.closing_sound_enabled', true)
            ->assertJsonPath(
                'data.template.background_sound',
                'ambient_rain_soft'
            )
            ->json('data.template');

        $list = $this->getJson('/api/v1/meditation/templates')
            ->assertStatus(200)
            ->json('data.templates');

        $this->assertCount(1, $list);
        $this->assertSame($template['id'], $list[0]['id']);

        $this->putJson(
            "/api/v1/meditation/templates/{$template['id']}",
            ['name' => 'Evening 15']
        )
            ->assertStatus(200)
            ->assertJsonPath('message', 'Template saved.')
            ->assertJsonPath('data.template.name', 'Evening 15');

        $this->deleteJson(
            "/api/v1/meditation/templates/{$template['id']}"
        )
            ->assertStatus(200)
            ->assertJsonPath('message', 'Template deleted.');

        $this->assertDatabaseMissing('meditation_templates', [
            'id' => $template['id'],
            'user_id' => $user->id,
        ]);
    }

    public function test_template_can_reference_a_meditation_track(): void
    {
        $meditation = $this->makePublishedMeditation(
            ['type' => 'free', 'title' => 'Sound Bed'],
            [['language' => 'en']]
        );

        Sanctum::actingAs(User::factory()->create());

        $this->postJson('/api/v1/meditation/templates', [
            'name' => 'Music Session',
            'duration_seconds' => 1200,
            'type' => 'free',
            'breathing_enabled' => false,
            'closing_sound_enabled' => false,
            'meditation_id' => $meditation->id,
        ])
            ->assertStatus(201)
            ->assertJsonPath(
                'data.template.meditation_id',
                $meditation->id
            );
    }

    public function test_template_validation_rejects_bad_input(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->postJson('/api/v1/meditation/templates', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors([
                'name',
                'duration_seconds',
                'type',
                'breathing_enabled',
                'closing_sound_enabled',
            ]);

        $this->postJson('/api/v1/meditation/templates', [
            'name' => 'Bad',
            'duration_seconds' => 30,
            'type' => 'sleep',
            'breathing_enabled' => 'yes',
            'closing_sound_enabled' => 'yes',
            'meditation_id' => 999999,
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors([
                'duration_seconds',
                'type',
                'breathing_enabled',
                'closing_sound_enabled',
                'meditation_id',
            ]);
    }

    public function test_templates_are_scoped_to_their_owner(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();

        $template = $owner->meditationTemplates()->create([
            'name' => 'Owner template',
            'duration_seconds' => 600,
            'type' => 'free',
            'breathing_enabled' => false,
            'closing_sound_enabled' => true,
        ]);

        Sanctum::actingAs($other);

        $this->getJson('/api/v1/meditation/templates')
            ->assertStatus(200)
            ->assertJsonCount(0, 'data.templates');

        $this->putJson(
            "/api/v1/meditation/templates/{$template->id}",
            ['name' => 'Hacked']
        )->assertStatus(403);

        $this->deleteJson(
            "/api/v1/meditation/templates/{$template->id}"
        )->assertStatus(403);
    }
}
