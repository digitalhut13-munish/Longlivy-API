<?php

namespace App\Http\Requests\Api\V1\Meditation;

use Illuminate\Foundation\Http\FormRequest;

class UpdateMeditationReminderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'time' => [
                'sometimes',
                'string',
                'regex:/^([01]\d|2[0-3]):[0-5]\d(:[0-5]\d)?$/',
            ],

            'days_of_week' => [
                'sometimes',
                'string',
                'max:30',
                'regex:/^[0-6](,[0-6]){0,6}$/',
            ],

            'label' => [
                'sometimes',
                'nullable',
                'string',
                'max:255',
            ],

            'enabled' => [
                'sometimes',
                'boolean',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'time.regex' => 'Reminder time must be in HH:MM or HH:MM:SS format.',

            'days_of_week.regex' => 'Days of week must be a comma separated list of'
                .' numbers from 0 (Sunday) to 6 (Saturday).',

            'label.max' => 'Label cannot be longer than 255 characters.',

            'enabled.boolean' => 'Enabled must be true or false.',
        ];
    }
}
