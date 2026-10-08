<?php

namespace App\Contracts\Integrations;

use App\Models\User;

interface PushNotificationService
{
    /**
     * Deliver a device push notification for an enabled event.
     *
     * In-app delivery always happens through the database notification
     * channel; this contract only covers the device push hop.
     *
     * @param  array<string, mixed>  $data
     */
    public function send(User $user, string $event, array $data = []): void;

    /**
     * Whether a real push transport is configured for this environment.
     */
    public function configured(): bool;
}
