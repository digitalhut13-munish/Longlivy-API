<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class MakeUserAdmin extends Command
{
    protected $signature = 'longlivy:admin
        {email : The email address of the user to promote}
        {--revoke : Remove admin access instead of granting it}';

    protected $description = 'Grant or revoke backend admin access (meditation catalog management) for a user.';

    public function handle(): int
    {
        $email = (string) $this->argument('email');
        $revoke = (bool) $this->option('revoke');

        $user = User::where('email', $email)->first();

        if ($user === null) {
            $this->error("No user found with email [{$email}].");

            return self::FAILURE;
        }

        $user->is_admin = ! $revoke;
        $user->save();

        $state = $revoke ? 'revoked' : 'granted';

        $this->info("Admin access {$state} for {$user->email}.");

        return self::SUCCESS;
    }
}
