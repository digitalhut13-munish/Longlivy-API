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
            'text' => ['nullable', 'string', 'max:5000'],
            'image_base64' => ['nullable', 'string', 'max:10000000'],
            'meal_type' => ['nullable', 'string', 'max:30'],
        ];
    }
}
