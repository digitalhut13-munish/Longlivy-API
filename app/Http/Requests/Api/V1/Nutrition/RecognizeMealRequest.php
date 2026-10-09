<?php

namespace App\Http\Requests\Api\V1\Nutrition;

use Illuminate\Foundation\Http\FormRequest;

class RecognizeMealRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'type' => ['required', 'in:photo,voice,text'],
            'text' => ['nullable', 'string', 'max:1000'],
            'meal_type' => ['nullable', 'string', 'max:30'],
            'locale' => ['nullable', 'string', 'max:10'],
            'image' => [
                'required_if:type,photo',
                'image',
                'mimes:jpeg,png,heic',
                'max:8192',
            ],
            'image_base64' => ['nullable', 'string', 'max:12000000'],
        ];
    }
}