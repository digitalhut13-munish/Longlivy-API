<?php

namespace App\Http\Requests\Api\V1\Weight;

use Illuminate\Foundation\Http\FormRequest;

class StoreWeightLogRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'weight' => [
                'required',
                'numeric',
                'min:1',
                'max:700',
            ],

            'unit' => [
                'nullable',
                'in:kg,lb',
            ],

            'logged_at' => [
                'nullable',
                'date',
            ],

            'source' => [
                'nullable',
                'in:manual,health_platform,wearable',
            ],

            'external_id' => [
                'nullable',
                'string',
                'max:191',
            ],

            'notes' => [
                'nullable',
                'string',
                'max:1000',
            ],
        ];
    }
}
