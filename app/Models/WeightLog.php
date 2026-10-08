<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class WeightLog extends Model
{
    use HasFactory;

    public const SOURCE_MANUAL = 'manual';

    public const SOURCE_HEALTH_PLATFORM = 'health_platform';

    public const SOURCE_WEARABLE = 'wearable';

    protected $fillable = [
        'user_id',
        'weight',
        'unit',
        'logged_at',
        'date',
        'source',
        'external_id',
        'notes',
    ];

    protected $casts = [
        'weight' => 'decimal:2',
        'logged_at' => 'datetime',
        'date' => 'date',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
