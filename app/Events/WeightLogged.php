<?php

namespace App\Events;

use App\Models\User;
use App\Models\WeightLog;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class WeightLogged
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly User $user,
        public readonly WeightLog $weightLog
    ) {
    }
}
