<?php

namespace App\Listeners;

use App\Events\NutritionDayChanged;
use App\Services\Energy\EnergyBalanceService;

class RecomputeEnergyBalanceOnNutritionChange
{
    public function __construct(
        private readonly EnergyBalanceService $balanceService
    ) {
    }

    public function handle(NutritionDayChanged $event): void
    {
        foreach (array_unique($event->dates) as $date) {
            $this->balanceService->recompute($event->user, $date);
        }
    }
}
