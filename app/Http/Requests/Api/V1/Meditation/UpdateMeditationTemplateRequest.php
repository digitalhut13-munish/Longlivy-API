<?php

namespace App\Http\Requests\Api\V1\Meditation;

use App\Models\MeditationTemplate;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateMeditationTemplateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'name' => [
                'sometimes',
                'string',
                'max:60',
            ],

            'duration_seconds' => [
                'sometimes',
                'integer',
                'min:60',
                'max:7200',
            ],

            'type' => [
                'sometimes',
                Rule::in([
                    MeditationTemplate::TYPE_GUIDED,
                    MeditationTemplate::TYPE_FREE,
                    MeditationTemplate::TYPE_BREATHING,
                ]),
            ],

            'breathing_enabled' => [
                'sometimes',
                'boolean',
            ],

            'closing_sound_enabled' => [
                'sometimes',
                'boolean',
            ],

            'background_sound' => [
                'sometimes',
                'nullable',
                'string',
                'max:100',
            ],

            'meditation_id' => [
                'sometimes',
                'nullable',
                'integer',
                'exists:meditations,id',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'name.max' => 'Template name cannot be longer than 60 characters.',
            'duration_seconds.min' => 'Duration must be at least 60 seconds.',
            'duration_seconds.max' => 'Duration cannot be more than 7200 seconds.',
            'type.in' => 'Template type must be guided, free, or breathing.',
            'breathing_enabled.boolean' => 'Breathing enabled must be true or false.',
            'closing_sound_enabled.boolean' => 'Closing sound enabled must be true or false.',
            'meditation_id.exists' => 'The selected meditation is not available.',
        ];
    }
}