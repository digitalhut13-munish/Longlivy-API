<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DailyEnergyBalance extends Model
{
    protected $fillable = [
        'user_id',
        'date',
        'bmr',
        'everyday_activity',
        'sport_activity',
        'imported_activity',
        'manual_activity',
        'total_consumption',
        'intake',
        'calorie_goal',
        'difference',
        'remaining',
        'over_by',
        'status',
        'computed_at',
    ];

    protected $casts = [
        'date' => 'date',
        'bmr' => 'decimal:2',
        'everyday_activity' => 'decimal:2',
        'sport_activity' => 'decimal:2',
        'imported_activity' => 'decimal:2',
        'manual_activity' => 'decimal:2',
        'total_consumption' => 'decimal:2',
        'intake' => 'decimal:2',
        'calorie_goal' => 'decimal:2',
        'difference' => 'decimal:2',
        'remaining' => 'decimal:2',
        'over_by' => 'decimal:2',
        'computed_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
