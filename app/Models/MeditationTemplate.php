<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MeditationTemplate extends Model
{
    use HasFactory;

    public const TYPE_GUIDED = 'guided';

    public const TYPE_FREE = 'free';

    public const TYPE_BREATHING = 'breathing';

    protected $fillable = [
        'user_id',
        'name',
        'duration_seconds',
        'type',
        'breathing_enabled',
        'closing_sound_enabled',
        'background_sound',
        'meditation_id',
    ];

    protected function casts(): array
    {
        return [
            'duration_seconds' => 'integer',
            'breathing_enabled' => 'boolean',
            'closing_sound_enabled' => 'boolean',
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
}