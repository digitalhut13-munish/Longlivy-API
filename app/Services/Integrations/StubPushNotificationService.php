<?php

namespace App\Services\Integrations;

use App\Contracts\Integrations\PushNotificationService;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class StubPushNotificationService implements PushNotificationService
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function send(User $user, string $event, array $data = []): void
    {
        Log::info('push.notification.stub', [
            'user_id' => $user->id,
            'event' => $event,
            'data' => $data,
        ]);
    }

    public function configured(): bool
    {
        return false;
    }
}
