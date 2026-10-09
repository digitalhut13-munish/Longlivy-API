<?php

namespace App\Http\Requests\Api\V1\Meditation;

use Illuminate\Foundation\Http\FormRequest;

class UpdateMeditationSessionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'notes' => [
                'sometimes',
                'nullable',
                'string',
                'max:1000',
            ],

            'position_seconds' => [
                'required_with:client_timestamp',
                'integer',
                'min:0',
            ],

            'active_seconds' => [
                'required_with:client_timestamp',
                'integer',
                'min:0',
            ],

            'paused_seconds' => [
                'required_with:client_timestamp',
                'integer',
                'min:0',
            ],

            'client_timestamp' => [
                'sometimes',
                'required',
                'date',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'notes.string' => 'Notes must be text.',

            'notes.max' => 'Notes cannot be longer than 1000 characters.',

            'position_seconds.required_with' => 'Position seconds are required together with the client timestamp.',

            'position_seconds.integer' => 'Position seconds must be a whole number.',

            'position_seconds.min' => 'Position seconds cannot be negative.',

            'active_seconds.required_with' => 'Active seconds are required together with the client timestamp.',

            'active_seconds.integer' => 'Active seconds must be a whole number.',

            'active_seconds.min' => 'Active seconds cannot be negative.',

            'paused_seconds.required_with' => 'Paused seconds are required together with the client timestamp.',

            'paused_seconds.integer' => 'Paused seconds must be a whole number.',

            'paused_seconds.min' => 'Paused seconds cannot be negative.',

            'client_timestamp.date' => 'Client timestamp must be a valid date.',
        ];
    }
}