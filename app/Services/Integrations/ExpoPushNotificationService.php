<?php

namespace App\Services\Integrations;

use App\Contracts\Integrations\PushNotificationService;
use App\Models\User;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Delivers device pushes through the Expo Push API. Expo accepts
 * ExponentPushToken addresses without any extra credentials; APNs and
 * FCM tokens can only be delivered once the platform credentials are
 * configured on the Expo push service, so they are skipped with a log
 * line until then.
 */
class ExpoPushNotificationService implements PushNotificationService
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function send(User $user, string $event, array $data = []): void
    {
        $tokens = $user->devices()
            ->where('provider', 'expo')
            ->pluck('token');

        if ($tokens->isEmpty()) {
            return;
        }

        $title = (string) ($data['title'] ?? 'Longlivy');
        $body = (string) ($data['body'] ?? 'You have a new notification.');
        $payload = array_diff_key($data, array_flip(['title', 'body']));

        foreach ($tokens as $token) {
            try {
                $response = Http::post(
                    (string) config('longlivy.push.expo_endpoint'),
                    [
                        'to' => $token,
                        'sound' => 'default',
                        'title' => $title,
                        'body' => $body,
                        'data' => $payload + ['event' => $event],
                    ]
                );

                if ($response->failed()) {
                    Log::warning('push.notification.expo_failed', [
                        'user_id' => $user->id,
                        'event' => $event,
                        'status' => $response->status(),
                    ]);
                }
            } catch (ConnectionException $exception) {
                Log::warning('push.notification.expo_unreachable', [
                    'user_id' => $user->id,
                    'event' => $event,
                    'error' => $exception->getMessage(),
                ]);
            }
        }
    }

    public function configured(): bool
    {
        return true;
    }
}
