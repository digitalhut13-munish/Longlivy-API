<?php

namespace App\Http\Requests\Api\V1\Activity;

use Illuminate\Foundation\Http\FormRequest;

class UpdateActivityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'type' => ['sometimes', 'string', 'in:running,walking,cycling,hiking,jogging,other'],
            'distance_meters' => ['sometimes', 'nullable', 'numeric', 'min:0', 'max:500000'],
            'calories_kcal' => ['sometimes', 'numeric', 'min:0', 'max:10000'],
            'avg_heart_rate' => ['sometimes', 'nullable', 'integer', 'min:0', 'max:400'],
            'steps' => ['sometimes', 'nullable', 'integer', 'min:0'],
            'notes' => ['sometimes', 'nullable', 'string', 'max:2000'],
        ];
    }
}