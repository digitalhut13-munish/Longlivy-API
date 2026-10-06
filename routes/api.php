<?php

use App\Http\Controllers\Api\V1\Auth\AuthController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\GoalController;
use App\Http\Controllers\Api\V1\StreakController;

Route::prefix('v1')->group(function () {

    /*
    |--------------------------------------------------------------------------
    | API Test
    |--------------------------------------------------------------------------
    */

    Route::get('/test', function () {
        return response()->json([
            'success' => true,
            'message' => 'Longlivy API is working',
            'version' => 'v1',
        ]);
    });

    /*
    |--------------------------------------------------------------------------
    | Authentication
    |--------------------------------------------------------------------------
    */

    Route::prefix('auth')->group(function () {

        Route::post('/register', [
            AuthController::class,
            'register',
        ]);

        Route::post('/login', [
            AuthController::class,
            'login',
        ]);

        Route::middleware('auth:sanctum')->group(function () {

            Route::get('/me', [
                AuthController::class,
                'me',
            ]);

            Route::post('/logout', [
                AuthController::class,
                'logout',
            ]);

        });

    });

    Route::middleware('auth:sanctum')->group(function () {

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

    });

});
