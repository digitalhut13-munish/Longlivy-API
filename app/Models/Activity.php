<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Activity extends Model
{
    public const TYPES = [
        'running',
        'walking',
        'cycling',
        'hiking',
        'jogging',
        'other',
    ];

    public const SOURCES = [
        'tracked',
        'manual',
        'imported',
    ];

    protected $fillable = [
        'user_id',
        'client_id',
        'type',
        'source',
        'started_at',
        'ended_at',
        'active_seconds',
        'paused_seconds',
        'distance_meters',
        'calories_kcal',
        'calculation_method',
        'avg_heart_rate',
        'steps',
        'route_polyline',
        'route_point_count',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'ended_at' => 'datetime',
            'active_seconds' => 'integer',
            'paused_seconds' => 'integer',
            'distance_meters' => 'float',
            'calories_kcal' => 'integer',
            'avg_heart_rate' => 'integer',
            'steps' => 'integer',
            'route_point_count' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function route(): ?array
    {
        if ($this->route_polyline === null) {
            return null;
        }

        return [
            'encoding' => 'polyline5',
            'points' => $this->route_polyline,
            'point_count' => (int) $this->route_point_count,
        ];
    }

    public function componentForBalance(): string
    {
        return match ($this->source) {
            'manual' => 'manual_activity',
            'imported' => 'imported_activity',
            default => 'sport_activity',
        };
    }
}