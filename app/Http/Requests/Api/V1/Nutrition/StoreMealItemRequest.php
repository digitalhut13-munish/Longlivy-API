<?php

namespace App\Http\Requests\Api\V1\Nutrition;

use App\Models\Food;
use Illuminate\Foundation\Http\FormRequest;

class StoreMealItemRequest extends FormRequest
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
            'calories' => ['required_without:food_id', 'numeric', 'min:0'],
            'protein' => ['nullable', 'numeric', 'min:0'],
            'carbohydrates' => ['nullable', 'numeric', 'min:0'],
            'fat' => ['nullable', 'numeric', 'min:0'],
            'fiber' => ['nullable', 'numeric', 'min:0'],
            'sugar' => ['nullable', 'numeric', 'min:0'],
            'saturated_fat' => ['nullable', 'numeric', 'min:0'],
            'sodium' => ['nullable', 'numeric', 'min:0'],
        ];
    }
}
