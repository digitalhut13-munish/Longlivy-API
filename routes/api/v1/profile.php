<?php

use App\Http\Controllers\Api\V1\ProfileController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Profile
|--------------------------------------------------------------------------
*/

Route::get('/profile', [
    ProfileController::class,
    'show',
]);

Route::put('/profile', [
    ProfileController::class,
    'update',
]);
