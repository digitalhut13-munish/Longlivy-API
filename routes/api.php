<?php

use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {

    require __DIR__.'/api/v1/test.php';
    require __DIR__.'/api/v1/auth.php';

    Route::middleware(['auth:sanctum', 'throttle:api'])->group(function () {

        require __DIR__.'/api/v1/goals.php';
        require __DIR__.'/api/v1/streaks.php';
        require __DIR__.'/api/v1/fasting.php';
        require __DIR__.'/api/v1/profile.php';
        require __DIR__.'/api/v1/weight.php';
        require __DIR__.'/api/v1/nutrition.php';
        require __DIR__.'/api/v1/energy.php';
        require __DIR__.'/api/v1/meditation.php';
        require __DIR__.'/api/v1/notifications.php';
        require __DIR__.'/api/v1/activities.php';

    });

});
