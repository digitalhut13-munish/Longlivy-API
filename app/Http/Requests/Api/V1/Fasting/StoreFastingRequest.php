<?php

namespace App\Http\Requests\Api\V1\Fasting;

use App\Models\Fasting;
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
            'fasting_plan_id' => [
                'nullable',
                'integer',
                Rule::exists('fasting_plans', 'id')
                    ->where('user_id', $this->user()?->id),
            ],

            'fasting_type' => [
                'required',
                'string',
                'max:50',
                Rule::in(Fasting::FASTING_TYPES),
            ],

            'planned_hours' => [
                'required_without:planned_minutes',
                'integer',
                'min:1',
                'max:' . Fasting::MAX_PLANNED_HOURS,
            ],

            'planned_minutes' => [
                'nullable',
                'integer',
                'min:0',
                'max:1439',
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

            'fasting_type.in' =>
                'The selected fasting type is invalid.',

            'planned_hours.required_without' =>
                'Planned hours is required.',

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