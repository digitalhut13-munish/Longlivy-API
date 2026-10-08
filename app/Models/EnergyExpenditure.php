<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EnergyExpenditure extends Model
{
    /**
     * @var array<int, string>
     */
    public const COMPONENTS = [
        'bmr',
        'everyday_activity',
        'sport_activity',
        'imported_activity',
        'manual_activity',
    ];

    /**
     * Components produced by the Longlivy calculation engine. They
     * are replaced wholesale on every recompute.
     *
     * @var array<int, string>
     */
    public const CALCULATED_COMPONENTS = [
        'bmr',
        'everyday_activity',
    ];

    protected $fillable = [
        'user_id',
        'date',
        'component',
        'source',
        'calories',
        'calculation_method',
        'calculation_version',
        'provider',
        'external_id',
        'calculated_at',
        'meta',
    ];

    protected $casts = [
        'date' => 'date',
        'calories' => 'decimal:2',
        'calculated_at' => 'datetime',
        'meta' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
