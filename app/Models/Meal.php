<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Meal extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'meal_type',
        'name',
        'logged_at',
        'date',
        'total_calories',
        'total_protein',
        'total_carbohydrates',
        'total_fat',
        'total_fiber',
        'total_sodium',
        'notes',
        'source',
    ];

    protected $casts = [
        'logged_at' => 'datetime',
        'date' => 'date',
        'total_calories' => 'decimal:2',
        'total_protein' => 'decimal:2',
        'total_carbohydrates' => 'decimal:2',
        'total_fat' => 'decimal:2',
        'total_fiber' => 'decimal:2',
        'total_sodium' => 'decimal:2',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(MealItem::class)->orderBy('position');
    }

    /**
     * @return array<int, string>
     */
    public static function types(): array
    {
        return ['breakfast', 'lunch', 'dinner', 'snack', 'custom'];
    }
}
