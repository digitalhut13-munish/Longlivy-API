<?php

namespace App\Http\Requests\Api\V1\Goal;

use Illuminate\Foundation\Http\FormRequest;

class StoreGoalProgressRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'date' => [
                'required',
                'date',
            ],

            'value' => [
                'required',
                'numeric',
                'min:0',
            ],

            'completed' => [
                'required',
                'boolean',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'date.required' =>
                'Progress date is required.',

            'value.required' =>
                'Progress value is required.',

            'value.numeric' =>
                'Progress value must be a number.',

            'completed.required' =>
                'Completed status is required.',

            'completed.boolean' =>
                'Completed status must be true or false.',
        ];
    }
}