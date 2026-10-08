<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Calorie Calculation
    |--------------------------------------------------------------------------
    |
    | The engine used for BMR / TDEE calculation. Every persisted value
    | stores the method and version it was produced with so calculations
    | stay reproducible after an algorithm change.
    |
    */

    'calorie' => [
        'method' => env('LONGLIVY_CALORIE_METHOD', 'Mifflin-St Jeor'),
        'version' => env('LONGLIVY_CALORIE_VERSION', '1.0'),

        'activity_factors' => [
            'sedentary' => 1.2,
            'light' => 1.375,
            'moderate' => 1.55,
            'high' => 1.725,
            'very_high' => 1.9,
        ],

        // Daily kcal adjustment applied to the total energy
        // consumption when the calorie target is calculated for a
        // weight goal instead of maintenance.
        'deficit' => (int) env('LONGLIVY_CALORIE_DEFICIT', 500),
        'surplus' => (int) env('LONGLIVY_CALORIE_SURPLUS', 300),
    ],

    /*
    |--------------------------------------------------------------------------
    | Nutrition Targets
    |--------------------------------------------------------------------------
    |
    | Share of daily calories used when the macro targets are calculated
    | automatically. Fiber is a fixed gram target.
    |
    */

    'nutrition' => [
        'macro_ratios' => [
            'protein' => 0.30,
            'carbohydrates' => 0.45,
            'fat' => 0.25,
        ],

        'fiber_target' => (float) env('LONGLIVY_FIBER_TARGET', 30),
    ],

    /*
    |--------------------------------------------------------------------------
    | Fasting
    |--------------------------------------------------------------------------
    */

    'fasting' => [
        // Fasts at or above this many hours must show a safety notice
        // before they can be started. The app never issues medical
        // clearance - the notice is informational only.
        'long_fast_hours' => (int) env('LONGLIVY_LONG_FAST_HOURS', 48),
    ],

    /*
    |--------------------------------------------------------------------------
    | Meditation
    |--------------------------------------------------------------------------
    */

    'meditation' => [
        // Minimum active meditation time, in minutes, for a day to count
        // towards the meditation streak. Configurable by the backend.
        'min_streak_minutes' => (int) env('LONGLIVY_MEDITATION_MIN_STREAK_MINUTES', 1),

        // Maximum technically configurable free-meditation duration.
        'max_duration_minutes' => (int) env('LONGLIVY_MEDITATION_MAX_MINUTES', 180),

        // Duration choices offered before a session is started.
        'preset_minutes' => [3, 5, 10, 15, 20, 30],
    ],

    /*
    |--------------------------------------------------------------------------
    | Data Sources
    |--------------------------------------------------------------------------
    |
    | Every health-related value carries a source so measured values,
    | estimates and manual entries stay distinguishable everywhere.
    |
    */

    'sources' => [
        'manual',
        'longlivy_calculated',
        'health_platform',
        'wearable',
        'barcode_database',
        'user_recipe',
        'ai_estimated',
        'device_imported',
        'user_manual',
    ],

];
