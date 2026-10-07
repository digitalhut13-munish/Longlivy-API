<?php

namespace App\Http\Requests\Api\V1\Fasting;

use Illuminate\Foundation\Http\FormRequest;

class EndFastingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'ended_at' => [
                'nullable',
                'date',
                'before_or_equal:now',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'ended_at.date' =>
                'End time must be a valid date.',

            'ended_at.before_or_equal' =>
                'End time cannot be in the future.',
        ];
    }
}
