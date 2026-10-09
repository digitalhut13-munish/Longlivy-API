<?php

namespace App\Http\Requests\Api\V1\Meditation;

use Illuminate\Foundation\Http\FormRequest;

class StoreMeditationReminderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('days_of_week') && is_array($this->days_of_week)) {
            $days = $this->days_of_week;

            $valid = array_reduce(
                $days,
                fn (bool $carry, $day) => $carry
                    && (is_int($day)
                        || preg_match('/^\d+$/', (string) $day))
                    && (int) $day >= 0 && (int) $day <= 6,
                true
            );

            if ($valid) {
                $this->merge([
                    'days_of_week' => implode(
                        ',',
                        array_map('intval', $days)
                    ),
                ]);
            }
        }
    }

    public function rules(): array
    {
        return [
            'time' => [
                'required',
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
            'time.required' => 'Reminder time is required.',

            'time.regex' => 'Reminder time must be in HH:MM or HH:MM:SS format.',

            'days_of_week.regex' => 'Days of week must be a comma separated list of'
                .' numbers from 0 (Sunday) to 6 (Saturday).',

            'label.max' => 'Label cannot be longer than 255 characters.',

            'enabled.boolean' => 'Enabled must be true or false.',
        ];
    }
}
