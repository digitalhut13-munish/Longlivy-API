<?php

namespace App\Http\Requests\Api\V1\FastingPlan;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreFastingPlanOverrideRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'action' => [
                'required',
                'string',
                Rule::in(['skip', 'reschedule']),
            ],

            'start_time' => [
                'nullable',
                'date_format:H:i',
                'required_if:action,reschedule',
            ],

            'end_time' => [
                'nullable',
                'date_format:H:i',
                'required_if:action,reschedule',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'action.in' => 'Action must be skip or reschedule.',
            'start_time.required_if' => 'Start time is required when rescheduling.',
            'end_time.required_if' => 'End time is required when rescheduling.',
        ];
    }
}