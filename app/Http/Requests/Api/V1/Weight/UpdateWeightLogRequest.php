<?php

namespace App\Http\Requests\Api\V1\Weight;

use Illuminate\Foundation\Http\FormRequest;

class UpdateWeightLogRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'weight' => [
                'sometimes',
                'numeric',
                'min:1',
                'max:700',
            ],

            'unit' => [
                'sometimes',
                'in:kg,lb',
            ],

            'logged_at' => [
                'sometimes',
                'date',
            ],

            'notes' => [
                'sometimes',
                'nullable',
                'string',
                'max:1000',
            ],
        ];
    }
}
