<?php

namespace App\Events;

use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Nutrition data for one or more days changed (food logged, edited
 * or removed). Carries the affected dates so downstream projections
 * - like the daily energy balance - refresh exactly where needed.
 */
class NutritionDayChanged
{
    use Dispatchable, SerializesModels;

    /**
     * @param  array<int, string>  $dates  Affected days as Y-m-d.
     */
    public function __construct(
        public readonly User $user,
        public readonly array $dates
    ) {
    }
}
