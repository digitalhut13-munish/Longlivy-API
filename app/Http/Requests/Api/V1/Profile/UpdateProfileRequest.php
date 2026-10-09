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
                'in:female,male,diverse',
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

            'goal' => [
                'sometimes',
                'nullable',
                'string',
                'in:weight_loss,maintenance,general_wellness,muscle_gain',
            ],

            'weight_change_pace_kg_per_week' => [
                'sometimes',
                'nullable',
                'numeric',
                'min:0.1',
                'max:1.0',
            ],

            'training_frequency' => [
                'sometimes',
                'nullable',
                'integer',
                'min:0',
                'max:14',
            ],

            'training_volume' => [
                'sometimes',
                'nullable',
                'string',
                'in:low,moderate,high',
            ],

            'preferred_fasting_method' => [
                'sometimes',
                'nullable',
                'string',
                'max:50',
            ],

            'micronutrient_focus' => [
                'sometimes',
                'nullable',
                'array',
                'max:50',
            ],

            'micronutrient_focus.*' => [
                'string',
                'max:50',
            ],

            'avatar_id' => [
                'sometimes',
                'nullable',
                'string',
                'max:191',
            ],

            'language' => [
                'sometimes',
                'nullable',
                'string',
                'in:en,de',
            ],
        ];
    }
}
