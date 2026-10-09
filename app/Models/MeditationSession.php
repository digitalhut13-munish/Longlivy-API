<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MeditationSession extends Model
{
    use HasFactory;

    public const STATUS_ACTIVE = 'active';

    public const STATUS_PAUSED = 'paused';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'user_id',
        'meditation_id',
        'type',
        'status',
        'planned_minutes',
        'actual_minutes',
        'started_at',
        'ended_at',
        'paused_at',
        'paused_seconds',
        'position_seconds',
        'active_seconds',
        'progress_updated_at',
        'date',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'planned_minutes' => 'integer',
            'actual_minutes' => 'integer',
            'started_at' => 'datetime',
            'ended_at' => 'datetime',
            'paused_at' => 'datetime',
            'paused_seconds' => 'integer',
            'position_seconds' => 'integer',
            'active_seconds' => 'integer',
            'progress_updated_at' => 'datetime',
            'date' => 'date',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function meditation(): BelongsTo
    {
        return $this->belongsTo(Meditation::class);
    }

    public function isRunning(): bool
    {
        return in_array($this->status, [
            self::STATUS_ACTIVE,
            self::STATUS_PAUSED,
        ], true);
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    public function isPaused(): bool
    {
        return $this->status === self::STATUS_PAUSED;
    }

    public function elapsedSeconds(): int
    {
        $end = $this->ended_at ?? now();

        if ($end->lessThan($this->started_at)) {
            return 0;
        }

        $paused = $this->paused_seconds;

        if ($this->isPaused() && $this->paused_at !== null) {
            $paused += $this->paused_at->diffInSeconds(now());
        }

        return max(
            0,
            $this->started_at->diffInSeconds($end) - $paused
        );
    }

    public function progressPercent(): float
    {
        if ($this->planned_minutes <= 0) {
            return 0.0;
        }

        $minutes = $this->actual_minutes !== null
            ? (float) $this->actual_minutes
            : round($this->elapsedSeconds() / 60, 2);

        return min(100, round(
            ($minutes / $this->planned_minutes) * 100,
            1
        ));
    }
}
