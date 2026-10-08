<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

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

    protected $fillable = [
        'category_id',
        'type',
        'title',
        'description',
        'duration_minutes',
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
            'released_at' => 'date',
            'version' => 'integer',
            'attribution_required' => 'boolean',
            'commercial_use_allowed' => 'boolean',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(
            MeditationCategory::class,
            'category_id'
        );
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(MeditationSession::class);
    }

    public function isPublished(): bool
    {
        return $this->status === self::STATUS_PUBLISHED;
    }
}
