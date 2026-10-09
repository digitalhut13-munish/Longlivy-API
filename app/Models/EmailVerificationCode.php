<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmailVerificationCode extends Model
{
    protected $fillable = [
        'user_id',
        'code_hash',
        'expires_at',
        'attempts',
        'last_attempt_at',
    ];

    public const MAX_ATTEMPTS = 5;

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'last_attempt_at' => 'datetime',
            'attempts' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null
            && $this->expires_at->isPast();
    }

    public function isLockedOut(): bool
    {
        return $this->attempts >= self::MAX_ATTEMPTS;
    }
}