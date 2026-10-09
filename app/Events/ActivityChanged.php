<?php

namespace App\Events;

use App\Models\Activity;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ActivityChanged
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly User $user,
        public readonly Activity $activity,
        public readonly bool $deleted = false
    ) {
    }
}