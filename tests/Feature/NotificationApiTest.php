<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class NotificationApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_device_can_be_registered_and_refreshed(): void
    {
        Sanctum::actingAs($user = User::factory()->create());

        $payload = [
            'token' => 'ExponentPushToken[aaaaaaaaaaaaaaaaa]',
            'provider' => 'expo',
            'platform' => 'ios',
            'app_version' => '0.1.0',
            'locale' => 'de-DE',
            'timezone' => 'Europe/Berlin',
        ];

        $this->postJson('/api/v1/devices', $payload)
            ->assertStatus(200)
            ->assertJsonPath('data.device.token', $payload['token'])
            ->assertJsonPath('data.device.platform', 'ios');

        $this->postJson('/api/v1/devices', array_merge($payload, [
            'platform' => 'android',
        ]))->assertStatus(200);

        $this->assertDatabaseCount('devices', 1);
        $this->assertDatabaseHas('devices', [
            'user_id' => $user->id,
            'token' => $payload['token'],
            'platform' => 'android',
        ]);
    }

    public function test_device_validation(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->postJson('/api/v1/devices', [
            'token' => 'short',
            'provider' => 'nope',
        ])->assertStatus(422)
            ->assertJsonValidationErrors([
                'provider',
                'platform',
                'app_version',
                'locale',
                'timezone',
            ]);
    }

    public function test_device_can_be_removed_on_logout(): void
    {
        Sanctum::actingAs($user = User::factory()->create());

        $user->devices()->create([
            'token' => 'ExponentPushToken[testtoken]',
            'provider' => 'expo',
            'platform' => 'ios',
        ]);

        $this->deleteJson(
            '/api/v1/devices/ExponentPushToken%5Btesttoken%5D'
        )
            ->assertStatus(200)
            ->assertJsonPath('message', 'Device removed.');

        $this->assertDatabaseCount('devices', 0);
    }

    public function test_inbox_lists_notifications_with_meta(): void
    {
        Sanctum::actingAs($user = User::factory()->create());

        $user->notifications()->create([
            'id' => '9b2f0000-0000-0000-0000-000000000001',
            'type' => 'fasting_streak_reached',
            'title' => 'New streak!',
            'body' => "You've completed 5 fasts in a row.",
            'data' => ['streak' => 5],
        ]);

        $user->notifications()->create([
            'id' => '9b2f0000-0000-0000-0000-000000000002',
            'type' => 'weight_reminder',
            'title' => 'Weigh-in',
            'body' => 'Time to check your weight.',
            'read_at' => now(),
        ]);

        $this->getJson('/api/v1/notifications')
            ->assertStatus(200)
            ->assertJsonPath('data.unread_count', 1)
            ->assertJsonPath(
                'data.notifications.0.type',
                'weight_reminder'
            )
            ->assertJsonPath('data.meta.current_page', 1)
            ->assertJsonCount(2, 'data.notifications');

        $this->getJson('/api/v1/notifications?unread_only=1')
            ->assertJsonCount(1, 'data.notifications')
            ->assertJsonPath(
                'data.notifications.0.type',
                'fasting_streak_reached'
            );
    }

    public function test_single_and_bulk_mark_read(): void
    {
        Sanctum::actingAs($user = User::factory()->create());

        $unread = $user->notifications()->create([
            'id' => '9b2f0000-0000-0000-0000-000000000011',
            'type' => 'fasting_begins',
            'title' => 'Fast starts',
        ]);

        $this->postJson(
            '/api/v1/notifications/'.$unread->id.'/read'
        )
            ->assertStatus(200)
            ->assertJsonPath('message', 'Marked as read.');

        $this->assertNotNull($unread->fresh()->read_at);

        $user->notifications()->create([
            'id' => '9b2f0000-0000-0000-0000-000000000012',
            'type' => 'fasting_ends',
            'title' => 'Fast ends',
        ]);

        $this->postJson('/api/v1/notifications/read-all')
            ->assertStatus(200)
            ->assertJsonPath('data.updated', 1);
    }

    public function test_only_own_notification_can_be_marked_read(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $other = User::factory()->create();
        $foreign = $other->notifications()->create([
            'id' => '9b2f0000-0000-0000-0000-000000000021',
            'type' => 'fasting_begins',
        ]);

        $this->postJson(
            '/api/v1/notifications/'.$foreign->id.'/read'
        )->assertStatus(403);
    }

    public function test_settings_default_and_update(): void
    {
        Sanctum::actingAs($user = User::factory()->create());

        $this->getJson('/api/v1/notification-settings')
            ->assertStatus(200)
            ->assertJsonPath('data.settings.fasting_begins', true)
            ->assertJsonPath('data.settings.weight_reminder', false)
            ->assertJsonCount(15, 'data.settings');

        $this->putJson('/api/v1/notification-settings', [
            'settings' => [
                'fasting_begins' => false,
                'weight_reminder' => true,
            ],
        ])
            ->assertStatus(200)
            ->assertJsonPath('data.settings.fasting_begins', false)
            ->assertJsonPath('data.settings.weight_reminder', true)
            ->assertJsonCount(15, 'data.settings');

        $this->assertDatabaseHas('notification_settings', [
            'user_id' => $user->id,
            'event' => 'weight_reminder',
            'enabled' => true,
        ]);
    }

    public function test_settings_rejects_unknown_type(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->putJson('/api/v1/notification-settings', [
            'settings' => [
                'not_a_real_event' => true,
            ],
        ])->assertStatus(422);
    }
}