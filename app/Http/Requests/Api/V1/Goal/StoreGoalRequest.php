<?php

namespace App\Http\Requests\Api\V1\Goal;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreGoalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'goal_type' => [
                'required',
                'string',
                'max:100',
            ],

            'name' => [
                'required',
                'string',
                'max:255',
            ],

            'description' => [
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
                'nullable',
                'date',
                'after_or_equal:start_date',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'goal_type.required' =>
                'Goal type is required.',

            'name.required' =>
                'Goal name is required.',

            'target_value.required' =>
                'Target value is required.',

            'target_value.numeric' =>
                'Target value must be a number.',

            'unit.required' =>
                'Goal unit is required.',

            'period.required' =>
                'Goal period is required.',

            'period.in' =>
                'Goal period must be day, week, or month.',

            'start_date.required' =>
                'Goal start date is required.',

            'end_date.after_or_equal' =>
                'End date must be after or equal to start date.',
        ];
    }
}