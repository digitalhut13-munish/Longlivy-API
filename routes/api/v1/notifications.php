<?php

use App\Http\Controllers\Api\V1\DeviceController;
use App\Http\Controllers\Api\V1\NotificationController;
use App\Http\Controllers\Api\V1\NotificationSettingController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Push devices
|--------------------------------------------------------------------------
*/

Route::post('/devices', [
    DeviceController::class,
    'register',
]);

Route::delete('/devices/{token}', [
    DeviceController::class,
    'destroy',
]);

/*
|--------------------------------------------------------------------------
| Notification inbox
|--------------------------------------------------------------------------
*/

Route::get('/notifications', [
    NotificationController::class,
    'index',
]);

Route::post('/notifications/read-all', [
    NotificationController::class,
    'readAll',
]);

Route::post('/notifications/{notification}/read', [
    NotificationController::class,
    'read',
])->whereUuid('notification');

/*
|--------------------------------------------------------------------------
| Notification preferences
|--------------------------------------------------------------------------
*/

Route::get('/notification-settings', [
    NotificationSettingController::class,
    'index',
]);

Route::put('/notification-settings', [
    NotificationSettingController::class,
    'update',
]);