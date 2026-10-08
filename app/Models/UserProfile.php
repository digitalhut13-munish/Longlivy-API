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
    ];

    protected $casts = [
        'date_of_birth' => 'date',
        'height' => 'decimal:2',
        'current_weight' => 'decimal:2',
        'body_fat_percentage' => 'decimal:2',
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
        return $this->timezone ?: 'UTC';
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}