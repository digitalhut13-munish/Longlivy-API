<?php

namespace App\Http\Requests\Api\V1\Nutrition;

use App\Models\Food;
use App\Models\Meal;
use Illuminate\Foundation\Http\FormRequest;

class StoreMealRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'meal_type' => ['required', 'string', 'in:'.implode(',', Meal::types())],
            'name' => ['nullable', 'string', 'max:191'],
            'logged_at' => ['nullable', 'date'],
            'date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'items' => ['sometimes', 'array'],
            'items.*.food_id' => ['nullable', 'integer'],
            'items.*.name' => ['required_without:items.*.food_id', 'string', 'max:191'],
            'items.*.quantity' => ['required', 'numeric', 'min:0.01'],
            'items.*.unit' => ['nullable', 'in:'.implode(',', Food::units())],
            'items.*.calories' => ['nullable', 'numeric', 'min:0'],
            'items.*.protein' => ['nullable', 'numeric', 'min:0'],
            'items.*.carbohydrates' => ['nullable', 'numeric', 'min:0'],
            'items.*.fat' => ['nullable', 'numeric', 'min:0'],
            'items.*.fiber' => ['nullable', 'numeric', 'min:0'],
        ];
    }
}
