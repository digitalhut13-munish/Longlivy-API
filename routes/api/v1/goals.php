<?php

use App\Http\Controllers\Api\V1\GoalController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Goals
|--------------------------------------------------------------------------
*/

Route::get('/goals', [
    GoalController::class,
    'index',
]);

Route::post('/goals', [
    GoalController::class,
    'store',
]);

Route::get('/goals/{goal}', [
    GoalController::class,
    'show',
]);

Route::put('/goals/{goal}', [
    GoalController::class,
    'update',
]);

Route::delete('/goals/{goal}', [
    GoalController::class,
    'destroy',
]);

/*
|--------------------------------------------------------------------------
| Goal Progress
|--------------------------------------------------------------------------
*/

Route::get('/goals/{goal}/progress', [
    GoalController::class,
    'progress',
]);

Route::post('/goals/{goal}/progress', [
    GoalController::class,
    'storeProgress',
]);
