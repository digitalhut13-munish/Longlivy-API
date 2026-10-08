<?php

namespace App\Http\Requests\Api\V1\Nutrition;

use App\Models\Favorite;
use Illuminate\Foundation\Http\FormRequest;

class StoreFavoriteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'type' => [
                'required',
                'string',
                'in:'
                .implode(',', [
                    Favorite::TYPE_FOOD,
                    Favorite::TYPE_MEAL,
                    Favorite::TYPE_MEDITATION,
                ]),
            ],

            'id' => ['required', 'integer'],
        ];
    }
}
