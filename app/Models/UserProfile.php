<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserProfile extends Model
{
    protected $fillable = [
        'user_id',
        'first_name',
        'last_name',
        'date_of_birth',
        'gender',
        'height',
        'height_unit',
        'current_weight',
        'weight_unit',
        'address',
        'timezone',
        'activity_level',
        'body_fat_percentage',
        'goal',
        'weight_change_pace_kg_per_week',
        'training_frequency',
        'training_volume',
        'preferred_fasting_method',
        'micronutrient_focus',
        'avatar_id',
        'language',
    ];

    protected $casts = [
        'date_of_birth' => 'date:Y-m-d',
        'height' => 'decimal:2',
        'current_weight' => 'decimal:2',
        'body_fat_percentage' => 'decimal:2',
        'weight_change_pace_kg_per_week' => 'decimal:2',
        'training_frequency' => 'integer',
        'micronutrient_focus' => 'array',
    ];

    /**
     * @return array<int, string>
     */
    public static function activityLevels(): array
    {
        return [
            'sedentary',
            'light',
            'moderate',
            'high',
            'very_high',
        ];
    }

    public function timezone(): string
    {
        return ! empty($this->attributes['timezone'])
            ? $this->attributes['timezone']
            : 'UTC';
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}