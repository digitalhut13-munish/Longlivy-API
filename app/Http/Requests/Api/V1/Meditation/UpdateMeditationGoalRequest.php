<?php

namespace App\Http\Requests\Api\V1\Meditation;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateMeditationGoalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'name' => [
                'sometimes',
                'string',
                'max:255',
            ],

            'description' => [
                'sometimes',
                'nullable',
                'string',
            ],

            'target_value' => [
                'sometimes',
                'numeric',
                'min:0',
            ],

            'unit' => [
                'sometimes',
                'string',
                'max:50',
            ],

            'period' => [
                'sometimes',
                Rule::in([
                    'day',
                    'week',
                    'month',
                ]),
            ],

            'start_date' => [
                'sometimes',
                'date',
            ],

            'end_date' => [
                'sometimes',
                'nullable',
                'date',
                'after_or_equal:start_date',
            ],

            'active' => [
                'sometimes',
                'boolean',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'target_value.numeric' => 'Goal target must be a number.',

            'period.in' => 'Goal period must be day, week, or month.',

            'end_date.after_or_equal' => 'Goal end date must be after or equal to start date.',

            'active.boolean' => 'Active must be true or false.',
        ];
    }
}
