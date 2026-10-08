<?php

use App\Http\Controllers\Api\V1\WeightLogController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Weight
|--------------------------------------------------------------------------
*/

Route::get('/weight-logs', [
    WeightLogController::class,
    'index',
]);

Route::post('/weight-logs', [
    WeightLogController::class,
    'store',
]);

Route::get('/weight-logs/latest', [
    WeightLogController::class,
    'latest',
]);

Route::get('/weight-logs/{weightLog}', [
    WeightLogController::class,
    'show',
]);

Route::put('/weight-logs/{weightLog}', [
    WeightLogController::class,
    'update',
]);

Route::delete('/weight-logs/{weightLog}', [
    WeightLogController::class,
    'destroy',
]);
