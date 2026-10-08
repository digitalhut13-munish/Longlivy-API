<?php

namespace App\Http\Requests\Api\V1\Nutrition;

use App\Models\Food;
use App\Models\Meal;
use Illuminate\Foundation\Http\FormRequest;

class StoreNutritionLogRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'food_id' => ['nullable', 'integer'],
            'name' => ['required_without:food_id', 'string', 'max:191'],
            'quantity' => ['required', 'numeric', 'min:0.01'],
            'unit' => ['nullable', 'in:'.implode(',', Food::units())],
            'meal_type' => ['required', 'string', 'in:'.implode(',', Meal::types())],
            'logged_at' => ['nullable', 'date'],
            'calories' => ['required_without:food_id', 'numeric', 'min:0'],
            'protein' => ['nullable', 'numeric', 'min:0'],
            'carbohydrates' => ['nullable', 'numeric', 'min:0'],
            'fat' => ['nullable', 'numeric', 'min:0'],
            'fiber' => ['nullable', 'numeric', 'min:0'],
        ];
    }
}
