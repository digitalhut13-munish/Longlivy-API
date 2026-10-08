<?php

namespace App\Http\Requests\Api\V1\Nutrition;

use Illuminate\Foundation\Http\FormRequest;

class UpdateFoodRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'max:191'],
            'brand' => ['nullable', 'string', 'max:191'],
            'category_id' => ['nullable', 'integer', 'exists:food_categories,id'],
            'barcode' => ['nullable', 'string', 'max:64'],
            'base_unit' => ['sometimes', 'in:g,ml'],
            'base_amount' => ['sometimes', 'numeric', 'min:0.01'],
            'calories' => ['sometimes', 'numeric', 'min:0'],
            'protein' => ['sometimes', 'numeric', 'min:0'],
            'carbohydrates' => ['sometimes', 'numeric', 'min:0'],
            'fat' => ['sometimes', 'numeric', 'min:0'],
            'fiber' => ['nullable', 'numeric', 'min:0'],
            'sugar' => ['nullable', 'numeric', 'min:0'],
            'saturated_fat' => ['nullable', 'numeric', 'min:0'],
            'sodium' => ['nullable', 'numeric', 'min:0'],
            'grams_per_unit' => ['nullable', 'numeric', 'min:0'],
            'ml_per_unit' => ['nullable', 'numeric', 'min:0'],
            'serving_amount' => ['nullable', 'numeric', 'min:0'],
        ];
    }
}
