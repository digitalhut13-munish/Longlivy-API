<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MeditationAudio extends Model
{
    use HasFactory;

    protected $fillable = [
        'meditation_id',
        'language',
        'storage_path',
        'format',
        'mime_type',
        'bitrate_kbps',
        'duration_seconds',
        'size_bytes',
    ];

    protected function casts(): array
    {
        return [
            'bitrate_kbps' => 'integer',
            'duration_seconds' => 'integer',
            'size_bytes' => 'integer',
        ];
    }

    public function meditation(): BelongsTo
    {
        return $this->belongsTo(Meditation::class);
    }

    public function url(): string
    {
        return $this->storage_path;
    }

    public function isStreamable(): bool
    {
        return true;
    }
}