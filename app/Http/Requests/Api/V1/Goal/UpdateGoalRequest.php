<?php

namespace App\Http\Requests\Api\V1\Goal;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateGoalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'goal_type' => [
                'sometimes',
                'string',
                'max:100',
            ],

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
}