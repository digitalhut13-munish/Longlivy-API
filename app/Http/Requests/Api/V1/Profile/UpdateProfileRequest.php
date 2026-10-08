<?php

namespace App\Http\Requests\Api\V1\Profile;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'first_name' => [
                'sometimes',
                'string',
                'max:100',
            ],

            'last_name' => [
                'sometimes',
                'string',
                'max:100',
            ],

            'date_of_birth' => [
                'sometimes',
                'nullable',
                'date',
            ],

            'gender' => [
                'sometimes',
                'nullable',
                'string',
                'max:50',
            ],

            'height' => [
                'sometimes',
                'nullable',
                'numeric',
                'min:1',
            ],

            'height_unit' => [
                'sometimes',
                'nullable',
                'in:cm,in',
            ],

            'current_weight' => [
                'sometimes',
                'nullable',
                'numeric',
                'min:1',
            ],

            'weight_unit' => [
                'sometimes',
                'nullable',
                'in:kg,lb',
            ],

            'body_fat_percentage' => [
                'sometimes',
                'nullable',
                'numeric',
                'min:1',
                'max:80',
            ],

            'address' => [
                'sometimes',
                'nullable',
                'string',
                'max:1000',
            ],

            'timezone' => [
                'sometimes',
                'string',
                'max:64',
                'timezone',
            ],

            'activity_level' => [
                'sometimes',
                'string',
                'in:sedentary,light,moderate,high,very_high',
            ],
        ];
    }
}
