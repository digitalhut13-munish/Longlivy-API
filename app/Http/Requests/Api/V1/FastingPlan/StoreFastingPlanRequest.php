<?php

namespace App\Http\Requests\Api\V1\FastingPlan;

use App\Models\FastingPlan;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreFastingPlanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'method' => [
                'required',
                'string',
                Rule::in(FastingPlan::METHODS),
            ],

            'category' => [
                'required',
                'string',
                Rule::in(FastingPlan::CATEGORIES),
            ],

            'recurring' => [
                'required',
                'boolean',
            ],

            'start_time' => [
                'required',
                'date_format:H:i',
            ],

            'end_time' => [
                'required',
                'date_format:H:i',
            ],

            'weekdays' => [
                'required',
                'array',
                'min:1',
            ],

            'weekdays.*' => [
                'integer',
                'between:0,6',
            ],

            'start_date' => [
                'required',
                'date_format:Y-m-d',
            ],

            'end_date' => [
                'nullable',
                'date_format:Y-m-d',
                'after_or_equal:start_date',
            ],

            'timezone' => [
                'required',
                'string',
                'max:64',
                'timezone',
            ],

            'fasting_hours' => [
                'required',
                'integer',
                'min:1',
                'max:96',
            ],

            'eating_hours' => [
                'required',
                'integer',
                'min:0',
                'max:23',
            ],

            'active' => [
                'required',
                'boolean',
            ],

            'notification_settings' => [
                'nullable',
                'array',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'method.in' => 'The selected fasting method is invalid.',
            'category.in' => 'The selected category is invalid.',
            'weekdays.required' => 'At least one weekday is required.',
            'end_date.after_or_equal' => 'End date must not be before the start date.',
        ];
    }
}