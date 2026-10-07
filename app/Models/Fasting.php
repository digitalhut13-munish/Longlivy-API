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

    protected $fillable = [
        'user_id',
        'fasting_type',
        'planned_hours',
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
            'started_at' => 'datetime',
            'ended_at' => 'datetime',
            'actual_hours' => 'decimal:2',
            'date' => 'date',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
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
