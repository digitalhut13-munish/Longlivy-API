<?php

namespace App\Listeners;

use App\Events\ProfileUpdated;
use App\Services\Energy\EnergyBalanceService;
use Carbon\Carbon;

class RecomputeEnergyBalanceOnProfileChange
{
    public function __construct(
        private readonly EnergyBalanceService $balanceService
    ) {
    }

    public function handle(ProfileUpdated $event): void
    {
        $timezone = $event->user->profile?->timezone() ?? 'UTC';

        $this->balanceService->recompute(
            $event->user,
            Carbon::now($timezone)->toDateString()
        );
    }
}
