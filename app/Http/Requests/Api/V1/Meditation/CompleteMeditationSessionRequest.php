<?php

namespace App\Http\Requests\Api\V1\Meditation;

use Illuminate\Foundation\Http\FormRequest;

class CompleteMeditationSessionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'ended_at' => [
                'sometimes',
                'nullable',
                'date',
                'before_or_equal:now',
            ],

            'active_seconds' => [
                'sometimes',
                'nullable',
                'integer',
                'min:0',
            ],

            'paused_seconds' => [
                'sometimes',
                'nullable',
                'integer',
                'min:0',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'ended_at.before_or_equal' => 'End time cannot be in the future.',
            'active_seconds.integer' => 'Active seconds must be a whole number.',
            'active_seconds.min' => 'Active seconds cannot be negative.',
            'paused_seconds.integer' => 'Paused seconds must be a whole number.',
            'paused_seconds.min' => 'Paused seconds cannot be negative.',
        ];
    }
}
