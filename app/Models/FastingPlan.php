<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FastingPlan extends Model
{
    public const METHODS = [
        '14:10',
        '16:8',
        '18:6',
        '20:4',
        '24h',
        '36h',
        '48h',
        '72h',
        '96h',
        'individual',
    ];

    public const CATEGORIES = [
        'intermittent',
        'longer',
        'individual',
    ];

    protected $fillable = [
        'user_id',
        'method',
        'category',
        'recurring',
        'start_time',
        'end_time',
        'weekdays',
        'start_date',
        'end_date',
        'timezone',
        'fasting_hours',
        'eating_hours',
        'active',
        'notification_settings',
    ];

    protected function casts(): array
    {
        return [
            'recurring' => 'boolean',
            'weekdays' => 'array',
            'start_date' => 'date',
            'end_date' => 'date',
            'fasting_hours' => 'integer',
            'eating_hours' => 'integer',
            'active' => 'boolean',
            'notification_settings' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function overrides(): HasMany
    {
        return $this->hasMany(FastingPlanOverride::class);
    }
}