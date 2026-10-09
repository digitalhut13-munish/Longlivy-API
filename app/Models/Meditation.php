<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Meditation extends Model
{
    use HasFactory;

    public const TYPE_GUIDED = 'guided';

    public const TYPE_FREE = 'free';

    public const TYPE_BREATHING = 'breathing';

    public const TYPE_INDIVIDUAL = 'individual';

    public const STATUS_DESIGN = 'design';

    public const STATUS_PUBLISHED = 'published';

    public const STATUS_DISABLED = 'disabled';

    public const STATUS_ARCHIVED = 'archived';

    public const AVAILABILITY_AVAILABLE = 'available';

    public const AVAILABILITY_COMING_SOON = 'coming_soon';

    protected $fillable = [
        'category_id',
        'type',
        'title',
        'description',
        'duration_minutes',
        'duration_seconds',
        'sound_category',
        'thumbnail_path',
        'availability',
        'is_premium',
        'breathing_pattern',
        'audio_url',
        'background_audio_url',
        'background_type',
        'language',
        'status',
        'released_at',
        'version',
        'source',
        'rights_holder',
        'license_type',
        'license_url',
        'license_status',
        'attribution_required',
        'commercial_use_allowed',
    ];

    protected function casts(): array
    {
        return [
            'duration_minutes' => 'integer',
            'duration_seconds' => 'integer',
            'released_at' => 'date',
            'version' => 'integer',
            'attribution_required' => 'boolean',
            'commercial_use_allowed' => 'boolean',
            'is_premium' => 'boolean',
            'breathing_pattern' => 'array',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(
            MeditationCategory::class,
            'category_id'
        );
    }

    public function audio(): HasMany
    {
        return $this->hasMany(MeditationAudio::class);
    }

    public function favorites(): MorphMany
    {
        return $this->morphMany(Favorite::class, 'favoritable');
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(MeditationSession::class);
    }

    public function durationSeconds(): int
    {
        return $this->duration_seconds
            ?? $this->duration_minutes * 60;
    }

    public function breathingPattern(): ?array
    {
        $pattern = $this->breathing_pattern;

        if (! is_array($pattern)) {
            return null;
        }

        return [
            'inhale_seconds' => (int) ($pattern['inhale_seconds'] ?? 0),
            'hold_seconds' => (int) ($pattern['hold_seconds'] ?? 0),
            'exhale_seconds' => (int) ($pattern['exhale_seconds'] ?? 0),
            'second_hold_seconds' => (int) ($pattern['second_hold_seconds'] ?? 0),
            'repetitions' => (int) ($pattern['repetitions'] ?? 1),
        ];
    }

    public function isPublished(): bool
    {
        return $this->status === self::STATUS_PUBLISHED;
    }
}
