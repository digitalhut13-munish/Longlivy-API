<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Fasting extends Model
{
    use HasFactory;

    public const STATUS_ONGOING = 'ongoing';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_FAILED = 'failed';

    public const STATUS_CANCELLED = 'cancelled';

    public const FASTING_TYPES = [
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

    public const MAX_PLANNED_HOURS = 96;

    protected $fillable = [
        'user_id',
        'fasting_plan_id',
        'fasting_type',
        'planned_hours',
        'planned_minutes',
        'started_at',
        'ended_at',
        'actual_hours',
        'status',
        'date',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'planned_hours' => 'integer',
            'planned_minutes' => 'integer',
            'started_at' => 'datetime',
            'ended_at' => 'datetime',
            'actual_hours' => 'float',
            'date' => 'date:Y-m-d',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(FastingPlan::class, 'fasting_plan_id');
    }

    public function plannedEnd(): \Illuminate\Support\Carbon
    {
        return $this->started_at->copy()
            ->addHours($this->planned_hours)
            ->addMinutes($this->planned_minutes ?? 0);
    }

    public function isOngoing(): bool
    {
        return $this->status === self::STATUS_ONGOING;
    }

    public function elapsedHours(): float
    {
        $end = $this->ended_at ?? now();

        if ($end->lessThan($this->started_at)) {
            return 0.0;
        }

        return round(
            $this->started_at->diffInSeconds($end) / 3600,
            2
        );
    }

    public function progressPercent(): float
    {
        if ($this->planned_hours <= 0) {
            return 0.0;
        }

        $hours = $this->actual_hours !== null
            ? (float) $this->actual_hours
            : $this->elapsedHours();

        return min(100, round(
            ($hours / $this->planned_hours) * 100,
            1
        ));
    }
}
