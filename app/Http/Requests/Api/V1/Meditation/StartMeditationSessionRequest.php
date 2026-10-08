<?php

namespace App\Http\Requests\Api\V1\Meditation;

use App\Models\Meditation;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StartMeditationSessionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'meditation_id' => [
                'sometimes',
                'nullable',
                'integer',
                'exists:meditations,id',
            ],

            'type' => [
                'required_without:meditation_id',
                'string',
                Rule::in([
                    Meditation::TYPE_GUIDED,
                    Meditation::TYPE_FREE,
                    Meditation::TYPE_BREATHING,
                    Meditation::TYPE_INDIVIDUAL,
                ]),
            ],

            'planned_minutes' => [
                'required',
                'integer',
                'min:1',
                'max:'.(int) config(
                    'longlivy.meditation.max_duration_minutes'
                ),
            ],

            'started_at' => [
                'sometimes',
                'nullable',
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
        $max = (int) config(
            'longlivy.meditation.max_duration_minutes'
        );

        return [
            'type.required_without' => 'Meditation type is required.',

            'type.in' => 'Meditation type must be guided, free, breathing,'
                .' or individual.',

            'planned_minutes.required' => 'Planned duration is required.',

            'planned_minutes.integer' => 'Planned duration must be a whole number.',

            'planned_minutes.min' => 'Planned duration must be at least 1 minute.',

            'planned_minutes.max' => "Planned duration cannot be more than {$max} minutes.",

            'meditation_id.exists' => 'The selected meditation is not available.',

            'started_at.before_or_equal' => 'Start time cannot be in the future.',
        ];
    }
}
