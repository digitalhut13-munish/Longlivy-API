<?php

namespace App\Http\Requests\Api\V1\Notification;

use App\Models\Notification;
use Illuminate\Foundation\Http\FormRequest;

class UpdateNotificationSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'settings' => [
                'required',
                'array',
            ],

            'settings.*' => [
                'boolean',
            ],
        ];
    }

    public function withValidator(\Illuminate\Validation\Validator $validator): void
    {
        $validator->after(function ($validator): void {
            $keys = array_keys($this->input('settings', []));

            foreach ($keys as $key) {
                if (! in_array($key, Notification::TYPES, true)) {
                    $validator->errors()->add(
                        'settings.'.$key,
                        'Unknown notification type "'.$key.'".'
                    );
                }
            }
        });
    }
}