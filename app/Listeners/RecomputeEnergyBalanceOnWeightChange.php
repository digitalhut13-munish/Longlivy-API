<?php

namespace App\Listeners;

use App\Events\WeightLogged;
use App\Services\Energy\EnergyBalanceService;

class RecomputeEnergyBalanceOnWeightChange
{
    public function __construct(
        private readonly EnergyBalanceService $balanceService
    ) {
    }

    public function handle(WeightLogged $event): void
    {
        $date = $event->weightLog->date->toDateString();

        $this->balanceService->recompute($event->user, $date);
    }
}
