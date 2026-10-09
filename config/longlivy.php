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
    | POST /energy/targets/calculate creates one goal per target, stored
    | in the goals module with period "day" and source
    | "longlivy_calculated":
    |   nutrition_calories  kcal    TDEE + adjustment (Mifflin-St Jeor TDEE
    |                              via the activity factor; adjustment -500 /
    |                              0 / +500 kcal, or weekly_change_kg *
    |                              7700 / 7 when a pace is given)
    |   nutrition_protein   g       30% of the calorie target / 4 kcal
    |   nutrition_carbs     g       45% of the calorie target / 4 kcal
    |   nutrition_fat       g       25% of the calorie target / 9 kcal
    |   nutrition_fiber     g       fixed gram target
    |
    | Manually edited targets keep source "manual" and are never
    | overwritten by a recalculation unless overwrite_manual is sent.
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

    /*
    |--------------------------------------------------------------------------
    | Authentication
    |--------------------------------------------------------------------------
    |
    | Sanctum bearer tokens issued by /auth/login and /auth/register
    | expire after this many days. There is no refresh endpoint; the
    | app signs the user out when an API call returns 401.
    |
    */

    'auth' => [
        'token_ttl_days' => (int) env('LONGLIVY_TOKEN_TTL_DAYS', 30),
    ],

    /*
    |--------------------------------------------------------------------------
    | Integrations
    |--------------------------------------------------------------------------
    |
    | Outbound data providers. The barcode driver resolves unknown EANs
    | against an external product database and caches the result as a
    | catalog food; the recognition driver builds meal drafts from photo,
    | voice or free-text input.
    |
    */

    'integrations' => [
        'barcode_driver' => env('LONGLIVY_BARCODE_DRIVER', 'open_food_facts'),
        'barcode_base_url' => env(
            'LONGLIVY_BARCODE_BASE_URL',
            'https://world.openfoodfacts.org'
        ),
        'barcode_timeout' => (int) env('LONGLIVY_BARCODE_TIMEOUT', 5),

        'recognition_driver' => env('LONGLIVY_RECOGNITION_DRIVER', 'ai'),
        'recognition_provider' => env(
            'LONGLIVY_RECOGNITION_PROVIDER',
            'longlivy-keyword'
        ),
    ],

];
