<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * App-owned database notification. Stored as rows keyed by a UUID so
 * the mobile app can render an inbox without the Laravel
 * notification class being hydratable on the device.
 */
class Notification extends Model
{
    public const TYPES = [
        'fasting_begins',
        'fasting_ends',
        'eating_phase_begins',
        'interim_goal_achieved',
        'fasting_almost_over',
        'planned_fast_not_started',
        'fasting_streak_reached',
        'personal_best_achieved',
        'activity_reminder',
        'activity_goal_achieved',
        'calorie_goal_almost_reached',
        'calorie_target_exceeded',
        'protein_goal_achieved',
        'weight_reminder',
        'meditation_reminder',
    ];

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'id',
        'user_id',
        'type',
        'title',
        'body',
        'data',
        'read_at',
    ];

    protected function casts(): array
    {
        return [
            'id' => 'string',
            'data' => 'array',
            'read_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isUnread(): bool
    {
        return $this->read_at === null;
    }

    public function markAsRead(): void
    {
        if ($this->isUnread()) {
            $this->read_at = now();
            $this->save();
        }
    }

    /**
     * @return array<int, string>
     */
    public static function types(): array
    {
        return self::TYPES;
    }
}