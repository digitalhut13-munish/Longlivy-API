<?php

namespace App\Http\Requests\Api\V1\Device;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RegisterDeviceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'token' => [
                'required',
                'string',
                'max:255',
            ],

            'provider' => [
                'required',
                'string',
                Rule::in(['expo', 'apns', 'fcm']),
            ],

            'platform' => [
                'required',
                'string',
                Rule::in(['ios', 'android']),
            ],

            'app_version' => [
                'required',
                'string',
                'max:30',
            ],

            'locale' => [
                'required',
                'string',
                'max:10',
            ],

            'timezone' => [
                'required',
                'string',
                'max:64',
                'timezone',
            ],
        ];
    }
}