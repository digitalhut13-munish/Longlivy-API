<?php

namespace App\Http\Requests\Api\V1\Nutrition;

use App\Models\Meal;
use Illuminate\Foundation\Http\FormRequest;

class UpdateMealRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'meal_type' => ['sometimes', 'string', 'in:'.implode(',', Meal::types())],
            'name' => ['nullable', 'string', 'max:191'],
            'logged_at' => ['sometimes', 'date'],
            'date' => ['sometimes', 'date'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
