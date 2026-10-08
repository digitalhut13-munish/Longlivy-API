<?php

namespace App\Http\Requests\Api\V1\Nutrition;

use App\Models\Food;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UpdateMealItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'food_id' => ['sometimes', 'nullable', 'integer'],
            'name' => ['sometimes', 'string', 'max:191'],
            'quantity' => ['sometimes', 'numeric', 'min:0.01'],
            'unit' => ['sometimes', 'in:'.implode(',', Food::units())],
            'calories' => ['sometimes', 'numeric', 'min:0'],
            'protein' => ['sometimes', 'numeric', 'min:0'],
            'carbohydrates' => ['sometimes', 'numeric', 'min:0'],
            'fat' => ['sometimes', 'numeric', 'min:0'],
            'fiber' => ['sometimes', 'numeric', 'min:0'],
            'sugar' => ['sometimes', 'numeric', 'min:0'],
            'saturated_fat' => ['sometimes', 'numeric', 'min:0'],
            'sodium' => ['sometimes', 'numeric', 'min:0'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if ($this->has('food_id')
                && $this->input('food_id') !== null
                && ! $this->has('quantity')
            ) {
                $validator->errors()->add(
                    'quantity',
                    'The quantity field is required when the food '
                    .'reference changes.'
                );
            }
        });
    }
}
