<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Goal extends Model
{
    use HasFactory;

    /**
     * Newly created goals are active by default and, unless created
     * by the target calculator, recorded as manually sourced.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'active' => true,
        'source' => 'manual',
    ];

    protected $fillable = [
        'user_id',
        'goal_type',
        'name',
        'description',
        'target_value',
        'unit',
        'period',
        'start_date',
        'end_date',
        'active',
        'source',
    ];

    protected function casts(): array
    {
        return [
            'target_value' => 'decimal:2',
            'start_date' => 'date',
            'end_date' => 'date',
            'active' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function progress(): HasMany
    {
        return $this->hasMany(GoalProgress::class);
    }
}