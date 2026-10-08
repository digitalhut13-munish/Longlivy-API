<?php

use App\Http\Controllers\Api\V1\EnergyController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Energy: basal rate, daily balance, calculated targets
|--------------------------------------------------------------------------
*/

Route::get('/energy/balance', [
    EnergyController::class,
    'balance',
]);

Route::get('/energy/bmr', [
    EnergyController::class,
    'bmr',
]);

Route::post('/energy/targets/calculate', [
    EnergyController::class,
    'calculateTargets',
]);
