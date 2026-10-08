<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Food extends Model
{
    /**
     * "food" is uncountable in the pluralizer, so the table name
     * must be stated explicitly.
     */
    protected $table = 'foods';

    protected $fillable = [
        'user_id',
        'name',
        'brand',
        'category_id',
        'barcode',
        'base_unit',
        'base_amount',
        'calories',
        'protein',
        'carbohydrates',
        'fat',
        'fiber',
        'sugar',
        'saturated_fat',
        'sodium',
        'grams_per_unit',
        'ml_per_unit',
        'serving_amount',
        'source',
        'verified',
        'is_custom',
        'external_id',
    ];

    protected $casts = [
        'base_amount' => 'decimal:2',
        'calories' => 'decimal:2',
        'protein' => 'decimal:2',
        'carbohydrates' => 'decimal:2',
        'fat' => 'decimal:2',
        'fiber' => 'decimal:2',
        'sugar' => 'decimal:2',
        'saturated_fat' => 'decimal:2',
        'sodium' => 'decimal:2',
        'grams_per_unit' => 'decimal:2',
        'ml_per_unit' => 'decimal:2',
        'serving_amount' => 'decimal:2',
        'verified' => 'boolean',
        'is_custom' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(FoodCategory::class, 'category_id');
    }

    public function mealItems(): HasMany
    {
        return $this->hasMany(MealItem::class);
    }

    public function isOwnedBy(User $user): bool
    {
        return $this->user_id !== null && $this->user_id === $user->id;
    }

    /**
     * @return array<int, string>
     */
    public static function units(): array
    {
        return ['g', 'kg', 'ml', 'l', 'piece', 'serving', 'custom'];
    }
}
