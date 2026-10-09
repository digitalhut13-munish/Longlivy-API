<?php

use App\Http\Controllers\Api\V1\Auth\AuthController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Authentication
|--------------------------------------------------------------------------
*/

Route::prefix('auth')->group(function () {

    Route::post('/register', [
        AuthController::class,
        'register',
    ])->middleware('throttle:auth');

    Route::post('/login', [
        AuthController::class,
        'login',
    ])->middleware('throttle:auth');

    Route::post('/forgot-password', [
        AuthController::class,
        'forgotPassword',
    ])->middleware('throttle:auth.forgot');

    Route::post('/reset-password', [
        AuthController::class,
        'resetPassword',
    ])->middleware('throttle:auth');

    Route::middleware('auth:sanctum')->group(function () {

        Route::get('/me', [
            AuthController::class,
            'me',
        ]);

        Route::post('/logout', [
            AuthController::class,
            'logout',
        ]);

        Route::delete('/account', [
            AuthController::class,
            'deleteAccount',
        ])->middleware('throttle:auth.delete');

        Route::post('/email/resend', [
            AuthController::class,
            'resendVerificationCode',
        ])->middleware('throttle:auth.verify');

        Route::post('/email/verify', [
            AuthController::class,
            'verifyEmail',
        ])->middleware('throttle:auth.verify');

    });

});
