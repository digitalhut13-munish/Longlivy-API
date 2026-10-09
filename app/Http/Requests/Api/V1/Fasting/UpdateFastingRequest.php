<?php

namespace App\Http\Requests\Api\V1\Fasting;

use App\Models\Fasting;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
                Rule::in(Fasting::FASTING_TYPES),
            ],

            'planned_hours' => [
                'sometimes',
                'integer',
                'min:1',
                'max:' . Fasting::MAX_PLANNED_HOURS,
            ],

            'planned_minutes' => [
                'sometimes',
                'nullable',
                'integer',
                'min:0',
                'max:1439',
            ],

            'started_at' => [
                'sometimes',
                'date',
                'before_or_equal:now',
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
            'fasting_type.in' =>
                'The selected fasting type is invalid.',

            'planned_hours.integer' =>
                'Planned hours must be a whole number.',

            'planned_hours.min' =>
                'Planned hours must be at least 1.',

            'planned_hours.max' =>
                'Planned hours cannot be more than ' .
                Fasting::MAX_PLANNED_HOURS . '.',

            'started_at.before_or_equal' =>
                'Start time cannot be in the future.',
        ];
    }
}