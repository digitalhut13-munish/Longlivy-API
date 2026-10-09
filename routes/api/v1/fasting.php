<?php

use App\Http\Controllers\Api\V1\FastingController;
use App\Http\Controllers\Api\V1\FastingPlanController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Fasting
|--------------------------------------------------------------------------
*/

Route::get('/fasting', [
    FastingController::class,
    'index',
]);

Route::post('/fasting', [
    FastingController::class,
    'store',
]);

Route::get('/fasting/active', [
    FastingController::class,
    'active',
]);

Route::get('/fasting/summary', [
    FastingController::class,
    'summary',
]);

Route::get('/fasting/{fasting}', [
    FastingController::class,
    'show',
])->whereNumber('fasting');

Route::put('/fasting/{fasting}', [
    FastingController::class,
    'update',
])->whereNumber('fasting');

Route::delete('/fasting/{fasting}', [
    FastingController::class,
    'destroy',
])->whereNumber('fasting');

Route::post('/fasting/{fasting}/end', [
    FastingController::class,
    'end',
])->whereNumber('fasting');

Route::post('/fasting/{fasting}/cancel', [
    FastingController::class,
    'cancel',
])->whereNumber('fasting');

/*
|--------------------------------------------------------------------------
| Fasting plans & day overrides
|--------------------------------------------------------------------------
*/

Route::get('/fasting/plans', [
    FastingPlanController::class,
    'index',
]);

Route::post('/fasting/plans', [
    FastingPlanController::class,
    'store',
]);

Route::get('/fasting/plans/{plan}', [
    FastingPlanController::class,
    'show',
])->whereNumber('plan');

Route::put('/fasting/plans/{plan}', [
    FastingPlanController::class,
    'update',
])->whereNumber('plan');

Route::delete('/fasting/plans/{plan}', [
    FastingPlanController::class,
    'destroy',
])->whereNumber('plan');

Route::get('/fasting/plans/{plan}/overrides', [
    FastingPlanController::class,
    'overrides',
])->whereNumber('plan');

Route::put('/fasting/plans/{plan}/overrides/{date}', [
    FastingPlanController::class,
    'saveOverride',
])
    ->whereNumber('plan')
    ->where('date', '[0-9]{4}-[0-9]{2}-[0-9]{2}');

Route::delete('/fasting/plans/{plan}/overrides/{date}', [
    FastingPlanController::class,
    'deleteOverride',
])
    ->whereNumber('plan')
    ->where('date', '[0-9]{4}-[0-9]{2}-[0-9]{2}');
