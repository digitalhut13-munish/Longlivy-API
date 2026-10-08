<?php

use Illuminate\Support\Facades\Route;

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
