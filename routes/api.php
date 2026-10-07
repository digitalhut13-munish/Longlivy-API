<?php

use App\Http\Controllers\Api\V1\Auth\AuthController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\FastingController;
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

    });

});
