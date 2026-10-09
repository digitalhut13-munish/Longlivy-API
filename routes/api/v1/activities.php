<?php

use App\Http\Controllers\Api\V1\ActivityController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Activities
|--------------------------------------------------------------------------
*/

Route::get('/activities', [
    ActivityController::class,
    'index',
]);

Route::post('/activities', [
    ActivityController::class,
    'store',
]);

Route::get('/activities/{activity}', [
    ActivityController::class,
    'show',
])->whereNumber('activity');

Route::put('/activities/{activity}', [
    ActivityController::class,
    'update',
])->whereNumber('activity');

Route::delete('/activities/{activity}', [
    ActivityController::class,
    'destroy',
])->whereNumber('activity');