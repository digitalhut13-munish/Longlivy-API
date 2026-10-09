<?php

namespace App\Http\Requests\Api\V1\Activity;

use App\Models\Activity;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreActivityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'client_id' => [
                'required',
                'string',
                'max:191',
            ],

            'type' => [
                'required',
                'string',
                Rule::in(Activity::TYPES),
            ],

            'source' => [
                'required',
                'string',
                Rule::in(Activity::SOURCES),
            ],

            'started_at' => [
                'required',
                'date',
            ],

            'ended_at' => [
                'required',
                'date',
                'after:started_at',
            ],

            'active_seconds' => [
                'required',
                'integer',
                'min:0',
            ],

            'paused_seconds' => [
                'required',
                'integer',
                'min:0',
            ],

            'distance_meters' => [
                'nullable',
                'numeric',
                'min:0',
                'max:500000',
            ],

            'calories_kcal' => [
                'required',
                'numeric',
                'min:0',
                'max:10000',
            ],

            'calculation_method' => [
                'required',
                'string',
                'max:60',
            ],

            'avg_heart_rate' => [
                'nullable',
                'integer',
                'min:0',
                'max:400',
            ],

            'steps' => [
                'nullable',
                'integer',
                'min:0',
            ],

            'route' => [
                'nullable',
                'array',
            ],

            'route.encoding' => [
                'required_with:route',
                'string',
                'max:20',
            ],

            'route.points' => [
                'required_with:route',
                'string',
                'max:1048576',
            ],

            'route.point_count' => [
                'required_with:route',
                'integer',
                'min:0',
            ],

            'notes' => [
                'nullable',
                'string',
                'max:2000',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'ended_at.after' => 'End time must be after start time.',
            'distance_meters.max' => 'Distance cannot exceed 500 km.',
            'calories_kcal.max' => 'Calories cannot exceed 10,000.',
            'route.points.max' => 'Route points are too large (max 1 MB).',
        ];
    }

    public function withValidator(\Illuminate\Validation\Validator $validator): void
    {
        $validator->after(function ($validator): void {
            $start = $this->input('started_at');
            $end = $this->input('ended_at');

            if ($start && $end) {
                $duration = strtotime($end) - strtotime($start);

                if ($duration > 24 * 3600) {
                    $validator->errors()->add(
                        'ended_at',
                        'Activity duration cannot exceed 24 hours.'
                    );
                }
            }
        });
    }
}