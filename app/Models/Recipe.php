<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Recipe extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'notes',
        'servings',
        'total_calories',
        'total_protein',
        'total_carbohydrates',
        'total_fat',
        'total_fiber',
    ];

    protected $casts = [
        'servings' => 'integer',
        'total_calories' => 'decimal:2',
        'total_protein' => 'decimal:2',
        'total_carbohydrates' => 'decimal:2',
        'total_fat' => 'decimal:2',
        'total_fiber' => 'decimal:2',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(RecipeItem::class)->orderBy('position');
    }
}
