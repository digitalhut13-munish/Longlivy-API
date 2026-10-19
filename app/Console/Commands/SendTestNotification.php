<?php

namespace App\Console\Commands;

use App\Models\Notification;
use App\Models\User;
use App\Services\Notification\NotificationService;
use Illuminate\Console\Command;

class SendTestNotification extends Command
{
    protected $signature = 'longlivy:notify-test
        {email : The email address of the user to notify}
        {--type=fasting_begins : The notification type to send}
        {--title=Test notification : Notification title}
        {--body=This is a test notification from the Longlivy backend. : Notification body}
        {--ignore-settings : Deliver even when the user disabled this type}';

    protected $description = 'Create a test notification (plus a device push when the expo driver is configured) for a user.';

    public function handle(NotificationService $notifications): int
    {
        $email = (string) $this->argument('email');
        $type = (string) $this->option('type');

        $user = User::where('email', $email)->first();

        if ($user === null) {
            $this->error("No user found with email [{$email}].");

            return self::FAILURE;
        }

        if (! in_array($type, Notification::types(), true)) {
            $this->error(
                "Unknown notification type [{$type}]. Valid types: "
                .implode(', ', Notification::types())
            );

            return self::FAILURE;
        }

        $notification = $notifications->send(
            $user,
            $type,
            (string) $this->option('title'),
            (string) $this->option('body'),
            ['test' => true],
            (bool) $this->option('ignore-settings')
        );

        if ($notification === null) {
            $this->warn(
                "User has [{$type}] disabled in notification settings; "
                .'nothing was delivered. Re-run with --ignore-settings '
                .'to force delivery.'
            );

            return self::SUCCESS;
        }

        $this->info("Notification {$notification->id} created for {$user->email}.");
        $this->line('Visible via: GET /api/v1/notifications');

        $driver = (string) config('longlivy.push.driver');
        $deviceCount = $user->devices()->where('provider', 'expo')->count();

        $this->line("Push driver: {$driver}");
        $this->line("Registered expo devices: {$deviceCount}");

        if ($driver !== 'expo') {
            $this->warn(
                'Push driver is [stub]: no device push was delivered. '
                .'Set LONGLIVY_PUSH_DRIVER=expo to deliver through the '
                .'Expo Push API.'
            );
        }

        return self::SUCCESS;
    }
}
