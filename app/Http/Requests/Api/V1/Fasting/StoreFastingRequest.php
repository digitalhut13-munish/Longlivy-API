<?php

namespace App\Http\Requests\Api\V1\Fasting;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreFastingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'fasting_type' => [
                'required',
                'string',
                'max:50',
            ],

            'planned_hours' => [
                'required',
                'integer',
                'min:1',
                'max:48',
            ],

            'started_at' => [
                'nullable',
                'date',
                'before_or_equal:now',
            ],

            'notes' => [
                'nullable',
                'string',
                'max:1000',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'fasting_type.required' =>
                'Fasting type is required.',

            'planned_hours.required' =>
                'Planned hours is required.',

            'planned_hours.integer' =>
                'Planned hours must be a whole number.',

            'planned_hours.min' =>
                'Planned hours must be at least 1.',

            'planned_hours.max' =>
                'Planned hours cannot be more than 48.',

            'started_at.before_or_equal' =>
                'Start time cannot be in the future.',
        ];
    }
}
