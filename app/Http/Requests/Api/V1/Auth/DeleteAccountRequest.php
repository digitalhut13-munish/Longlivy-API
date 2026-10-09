<?php

namespace App\Http\Requests\Api\V1\Auth;

use Illuminate\Foundation\Http\FormRequest;

class DeleteAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'password' => [
                'required',
                'string',
                'current_password',
            ],
            'confirmation' => [
                'required',
                'string',
                'max:10',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'password.required' => 'The password is required.',
            'password.current_password' => 'The password is incorrect.',
            'confirmation.required' => 'Type DELETE to confirm.',
            'confirmation.max' => 'Type DELETE to confirm.',
        ];
    }
}