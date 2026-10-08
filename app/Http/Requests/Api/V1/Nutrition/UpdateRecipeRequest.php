<?php

namespace App\Http\Requests\Api\V1\Nutrition;

use App\Models\Food;
use Illuminate\Foundation\Http\FormRequest;

class UpdateRecipeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'max:191'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'servings' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'items' => ['sometimes', 'array', 'min:1'],
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
