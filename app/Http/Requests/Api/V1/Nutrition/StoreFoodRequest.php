<?php

namespace App\Http\Requests\Api\V1\Nutrition;

use Illuminate\Foundation\Http\FormRequest;

class StoreFoodRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:191'],
            'brand' => ['nullable', 'string', 'max:191'],
            'category_id' => ['nullable', 'integer', 'exists:food_categories,id'],
            'barcode' => ['nullable', 'string', 'max:64'],
            'base_unit' => ['nullable', 'in:g,ml'],
            'base_amount' => ['nullable', 'numeric', 'min:0.01'],
            'calories' => ['required', 'numeric', 'min:0'],
            'protein' => ['required', 'numeric', 'min:0'],
            'carbohydrates' => ['required', 'numeric', 'min:0'],
            'fat' => ['required', 'numeric', 'min:0'],
            'fiber' => ['nullable', 'numeric', 'min:0'],
            'sugar' => ['nullable', 'numeric', 'min:0'],
            'saturated_fat' => ['nullable', 'numeric', 'min:0'],
            'sodium' => ['nullable', 'numeric', 'min:0'],
            'grams_per_unit' => ['nullable', 'numeric', 'min:0'],
            'ml_per_unit' => ['nullable', 'numeric', 'min:0'],
            'serving_amount' => ['nullable', 'numeric', 'min:0'],
            'source' => ['nullable', 'in:manual,barcode_database,user_recipe,ai_estimated'],
        ];
    }
}
