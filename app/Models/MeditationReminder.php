<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MeditationReminder extends Model
{
    use HasFactory;

    /**
     * Defaults applied on creation so freshly created models expose
     * the same values the database defaults would persist.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'days_of_week' => '0,1,2,3,4,5,6',
        'enabled' => true,
    ];

    protected $fillable = [
        'user_id',
        'time',
        'days_of_week',
        'label',
        'enabled',
    ];

    protected function casts(): array
    {
        return [
            'enabled' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function runsOnDay(int $dayOfWeek): bool
    {
        $days = array_map(
            'intval',
            explode(',', (string) $this->days_of_week)
        );

        return in_array($dayOfWeek, $days, true);
    }
}
