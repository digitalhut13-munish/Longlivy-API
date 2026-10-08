<?php

namespace App\Http\Requests\Api\V1\Meditation;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreMeditationGoalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:255',
            ],

            'description' => [
                'sometimes',
                'nullable',
                'string',
            ],

            'target_value' => [
                'required',
                'numeric',
                'min:0',
            ],

            'unit' => [
                'required',
                'string',
                'max:50',
            ],

            'period' => [
                'required',
                Rule::in([
                    'day',
                    'week',
                    'month',
                ]),
            ],

            'start_date' => [
                'required',
                'date',
            ],

            'end_date' => [
                'sometimes',
                'nullable',
                'date',
                'after_or_equal:start_date',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Goal name is required.',

            'target_value.required' => 'Goal target is required.',

            'target_value.numeric' => 'Goal target must be a number.',

            'unit.required' => 'Goal unit is required.',

            'period.required' => 'Goal period is required.',

            'period.in' => 'Goal period must be day, week, or month.',

            'start_date.required' => 'Goal start date is required.',

            'end_date.after_or_equal' => 'Goal end date must be after or equal to start date.',
        ];
    }
}
