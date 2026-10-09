<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FastingPlanOverride extends Model
{
    public const ACTION_SKIP = 'skip';

    public const ACTION_RESCHEDULE = 'reschedule';

    protected $fillable = [
        'plan_id',
        'user_id',
        'date',
        'action',
        'start_time',
        'end_time',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date:Y-m-d',
        ];
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(FastingPlan::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}