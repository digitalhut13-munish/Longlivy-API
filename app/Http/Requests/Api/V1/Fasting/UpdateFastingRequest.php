<?php

namespace App\Http\Requests\Api\V1\Fasting;

use Illuminate\Foundation\Http\FormRequest;

class UpdateFastingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'fasting_type' => [
                'sometimes',
                'string',
                'max:50',
            ],

            'planned_hours' => [
                'sometimes',
                'integer',
                'min:1',
                'max:48',
            ],

            'notes' => [
                'sometimes',
                'nullable',
                'string',
                'max:1000',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'fasting_type.string' =>
                'Fasting type must be text.',

            'planned_hours.integer' =>
                'Planned hours must be a whole number.',

            'planned_hours.min' =>
                'Planned hours must be at least 1.',

            'planned_hours.max' =>
                'Planned hours cannot be more than 48.',
        ];
    }
}
