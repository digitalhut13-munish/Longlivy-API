<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserStreak extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'type',
        'current_streak',
        'longest_streak',
        'current_streak_started_at',
        'last_completed_date',
    ];

    protected function casts(): array
    {
        return [
            'current_streak_started_at' => 'date',
            'last_completed_date' => 'date',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}