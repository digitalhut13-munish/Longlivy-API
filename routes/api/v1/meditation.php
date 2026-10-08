<?php

use App\Http\Controllers\Api\V1\MeditationController;
use App\Http\Controllers\Api\V1\MeditationGoalController;
use App\Http\Controllers\Api\V1\MeditationReminderController;
use App\Http\Controllers\Api\V1\MeditationSessionController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Meditation
|--------------------------------------------------------------------------
|
| Static routes are registered before /meditation/{meditation} so the
| content detail route never captures them.
|
*/

Route::get('/meditation/home', [
    MeditationController::class,
    'home',
]);

Route::get('/meditation/categories', [
    MeditationController::class,
    'categories',
]);

Route::get('/meditation/stats', [
    MeditationController::class,
    'stats',
]);

Route::get('/meditation/sessions', [
    MeditationSessionController::class,
    'index',
]);

Route::get('/meditation/sessions/active', [
    MeditationSessionController::class,
    'active',
]);

Route::post('/meditation/sessions', [
    MeditationSessionController::class,
    'store',
]);

Route::get('/meditation/sessions/{session}', [
    MeditationSessionController::class,
    'show',
]);

Route::put('/meditation/sessions/{session}', [
    MeditationSessionController::class,
    'update',
]);

Route::delete('/meditation/sessions/{session}', [
    MeditationSessionController::class,
    'destroy',
]);

Route::post('/meditation/sessions/{session}/pause', [
    MeditationSessionController::class,
    'pause',
]);

Route::post('/meditation/sessions/{session}/resume', [
    MeditationSessionController::class,
    'resume',
]);

Route::post('/meditation/sessions/{session}/complete', [
    MeditationSessionController::class,
    'complete',
]);

Route::post('/meditation/sessions/{session}/cancel', [
    MeditationSessionController::class,
    'cancel',
]);

Route::get('/meditation/goals', [
    MeditationGoalController::class,
    'index',
]);

Route::post('/meditation/goals', [
    MeditationGoalController::class,
    'store',
]);

Route::get('/meditation/goals/{goal}', [
    MeditationGoalController::class,
    'show',
]);

Route::put('/meditation/goals/{goal}', [
    MeditationGoalController::class,
    'update',
]);

Route::delete('/meditation/goals/{goal}', [
    MeditationGoalController::class,
    'destroy',
]);

Route::get('/meditation/goals/{goal}/progress', [
    MeditationGoalController::class,
    'progress',
]);

Route::post('/meditation/goals/{goal}/progress', [
    MeditationGoalController::class,
    'storeProgress',
]);

Route::get('/meditation/reminders', [
    MeditationReminderController::class,
    'index',
]);

Route::post('/meditation/reminders', [
    MeditationReminderController::class,
    'store',
]);

Route::put('/meditation/reminders/{reminder}', [
    MeditationReminderController::class,
    'update',
]);

Route::delete('/meditation/reminders/{reminder}', [
    MeditationReminderController::class,
    'destroy',
]);

Route::get('/meditation', [
    MeditationController::class,
    'index',
]);

Route::get('/meditation/{meditation}', [
    MeditationController::class,
    'show',
]);
