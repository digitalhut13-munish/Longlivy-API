<?php

use App\Http\Controllers\Api\V1\MeditationController;
use App\Http\Controllers\Api\V1\MeditationGoalController;
use App\Http\Controllers\Api\V1\MeditationReminderController;
use App\Http\Controllers\Api\V1\MeditationSessionController;
use App\Http\Controllers\Api\V1\MeditationTemplateController;
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

Route::post('/meditation/categories', [
    MeditationController::class,
    'storeCategory',
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
])->whereNumber('session');

Route::put('/meditation/sessions/{session}', [
    MeditationSessionController::class,
    'update',
])->whereNumber('session')
    ->middleware('throttle:meditation.progress');

Route::delete('/meditation/sessions/{session}', [
    MeditationSessionController::class,
    'destroy',
])->whereNumber('session');

Route::post('/meditation/sessions/{session}/pause', [
    MeditationSessionController::class,
    'pause',
])->whereNumber('session');

Route::post('/meditation/sessions/{session}/resume', [
    MeditationSessionController::class,
    'resume',
])->whereNumber('session');

Route::post('/meditation/sessions/{session}/complete', [
    MeditationSessionController::class,
    'complete',
])->whereNumber('session');

Route::post('/meditation/sessions/{session}/cancel', [
    MeditationSessionController::class,
    'cancel',
])->whereNumber('session');

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
])->whereNumber('goal');

Route::put('/meditation/goals/{goal}', [
    MeditationGoalController::class,
    'update',
])->whereNumber('goal');

Route::delete('/meditation/goals/{goal}', [
    MeditationGoalController::class,
    'destroy',
])->whereNumber('goal');

Route::get('/meditation/goals/{goal}/progress', [
    MeditationGoalController::class,
    'progress',
])->whereNumber('goal');

Route::post('/meditation/goals/{goal}/progress', [
    MeditationGoalController::class,
    'storeProgress',
])->whereNumber('goal');

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
])->whereNumber('reminder');

Route::delete('/meditation/reminders/{reminder}', [
    MeditationReminderController::class,
    'destroy',
])->whereNumber('reminder');

Route::get('/meditation/templates', [
    MeditationTemplateController::class,
    'index',
]);

Route::post('/meditation/templates', [
    MeditationTemplateController::class,
    'store',
]);

Route::put('/meditation/templates/{template}', [
    MeditationTemplateController::class,
    'update',
])->whereNumber('template');

Route::delete('/meditation/templates/{template}', [
    MeditationTemplateController::class,
    'destroy',
])->whereNumber('template');

Route::get('/meditation', [
    MeditationController::class,
    'index',
]);

Route::post('/meditation', [
    MeditationController::class,
    'store',
]);

Route::get('/meditation/{meditation}', [
    MeditationController::class,
    'show',
])->whereNumber('meditation');
