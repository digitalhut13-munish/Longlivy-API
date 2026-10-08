<?php

use App\Http\Controllers\Api\V1\StreakController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Streaks
|--------------------------------------------------------------------------
*/

Route::get('/streaks', [
    StreakController::class,
    'index',
]);

Route::get('/streaks/{type}', [
    StreakController::class,
    'show',
]);
