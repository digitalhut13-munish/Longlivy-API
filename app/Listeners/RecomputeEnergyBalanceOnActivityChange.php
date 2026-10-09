<?php

namespace App\Listeners;

use App\Events\ActivityChanged;
use App\Services\Energy\EnergyBalanceService;

class RecomputeEnergyBalanceOnActivityChange
{
    public function __construct(
        private readonly EnergyBalanceService $balanceService
    ) {
    }

    public function handle(ActivityChanged $event): void
    {
        if ($event->deleted) {
            $this->balanceService->removeActivity(
                $event->user,
                $event->activity
            );

            return;
        }

        $this->balanceService->syncActivity(
            $event->user,
            $event->activity
        );
    }
}