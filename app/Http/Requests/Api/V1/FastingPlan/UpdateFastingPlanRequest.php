<?php

namespace App\Http\Requests\Api\V1\FastingPlan;

use App\Models\FastingPlan;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateFastingPlanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'method' => [
                'sometimes',
                'string',
                Rule::in(FastingPlan::METHODS),
            ],

            'category' => [
                'sometimes',
                'string',
                Rule::in(FastingPlan::CATEGORIES),
            ],

            'recurring' => [
                'sometimes',
                'boolean',
            ],

            'start_time' => [
                'sometimes',
                'date_format:H:i',
            ],

            'end_time' => [
                'sometimes',
                'date_format:H:i',
            ],

            'weekdays' => [
                'sometimes',
                'array',
                'min:1',
            ],

            'weekdays.*' => [
                'integer',
                'between:0,6',
            ],

            'start_date' => [
                'sometimes',
                'date_format:Y-m-d',
            ],

            'end_date' => [
                'nullable',
                'date_format:Y-m-d',
                'after_or_equal:start_date',
            ],

            'timezone' => [
                'sometimes',
                'string',
                'max:64',
                'timezone',
            ],

            'fasting_hours' => [
                'sometimes',
                'integer',
                'min:1',
                'max:96',
            ],

            'eating_hours' => [
                'sometimes',
                'integer',
                'min:0',
                'max:23',
            ],

            'active' => [
                'sometimes',
                'boolean',
            ],

            'notification_settings' => [
                'sometimes',
                'nullable',
                'array',
            ],
        ];
    }
}