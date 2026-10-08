<?php

use App\Http\Controllers\Api\V1\FastingController;
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
]);

Route::put('/fasting/{fasting}', [
    FastingController::class,
    'update',
]);

Route::delete('/fasting/{fasting}', [
    FastingController::class,
    'destroy',
]);

Route::post('/fasting/{fasting}/end', [
    FastingController::class,
    'end',
]);
