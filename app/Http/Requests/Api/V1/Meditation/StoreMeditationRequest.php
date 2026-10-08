<?php

namespace App\Http\Requests\Api\V1\Meditation;

use App\Models\Meditation;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreMeditationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'title' => [
                'required',
                'string',
                'max:255',
            ],

            'type' => [
                'required',
                Rule::in([
                    Meditation::TYPE_GUIDED,
                    Meditation::TYPE_FREE,
                    Meditation::TYPE_BREATHING,
                    Meditation::TYPE_INDIVIDUAL,
                ]),
            ],

            'duration_minutes' => [
                'required',
                'integer',
                'min:1',
                'max:600',
            ],

            'category_id' => [
                'sometimes',
                'nullable',
                'integer',
                'exists:meditation_categories,id',
            ],

            'description' => [
                'sometimes',
                'nullable',
                'string',
            ],

            'audio_url' => [
                'sometimes',
                'nullable',
                'url',
                'max:500',
            ],

            'background_audio_url' => [
                'sometimes',
                'nullable',
                'url',
                'max:500',
            ],

            'background_type' => [
                'sometimes',
                'string',
                'max:50',
            ],

            'language' => [
                'sometimes',
                'string',
                'max:10',
            ],

            'status' => [
                'sometimes',
                Rule::in([
                    Meditation::STATUS_DESIGN,
                    Meditation::STATUS_PUBLISHED,
                    Meditation::STATUS_DISABLED,
                    Meditation::STATUS_ARCHIVED,
                ]),
            ],

            'released_at' => [
                'sometimes',
                'nullable',
                'date',
            ],

            'source' => [
                'sometimes',
                'nullable',
                'string',
                'max:255',
            ],

            'rights_holder' => [
                'sometimes',
                'nullable',
                'string',
                'max:255',
            ],

            'license_type' => [
                'sometimes',
                'nullable',
                'string',
                'max:100',
            ],

            'license_url' => [
                'sometimes',
                'nullable',
                'url',
                'max:500',
            ],

            'license_status' => [
                'sometimes',
                'string',
                'max:50',
            ],

            'attribution_required' => [
                'sometimes',
                'boolean',
            ],

            'commercial_use_allowed' => [
                'sometimes',
                'boolean',
            ],

            'version' => [
                'sometimes',
                'integer',
                'min:1',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'Meditation title is required.',

            'type.required' => 'Meditation type is required.',

            'type.in' => 'Meditation type must be guided, free, breathing,'
                .' or individual.',

            'duration_minutes.required' => 'Duration is required.',

            'duration_minutes.integer' => 'Duration must be a whole number.',

            'duration_minutes.min' => 'Duration must be at least 1 minute.',

            'duration_minutes.max' => 'Duration cannot be more than 600 minutes.',

            'category_id.exists' => 'The selected category does not exist.',

            'audio_url.url' => 'Audio URL must be a valid URL.',

            'background_audio_url.url' => 'Background audio URL must be a valid URL.',

            'status.in' => 'Status must be design, published, disabled, or archived.',

            'license_url.url' => 'License URL must be a valid URL.',

            'version.integer' => 'Version must be a whole number.',
        ];
    }
}
