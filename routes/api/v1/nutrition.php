<?php

use App\Http\Controllers\Api\V1\FavoriteController;
use App\Http\Controllers\Api\V1\FoodController;
use App\Http\Controllers\Api\V1\MealController;
use App\Http\Controllers\Api\V1\NutritionController;
use App\Http\Controllers\Api\V1\RecipeController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Foods
|--------------------------------------------------------------------------
*/

Route::get('/foods', [
    FoodController::class,
    'index',
]);

Route::post('/foods', [
    FoodController::class,
    'store',
]);

Route::get('/food-categories', [
    FoodController::class,
    'categories',
]);

Route::get('/foods/barcode/{barcode}', [
    FoodController::class,
    'barcode',
])->where('barcode', '[A-Za-z0-9\-]+');

Route::get('/foods/{food}', [
    FoodController::class,
    'show',
])->whereNumber('food');

Route::put('/foods/{food}', [
    FoodController::class,
    'update',
])->whereNumber('food');

Route::delete('/foods/{food}', [
    FoodController::class,
    'destroy',
])->whereNumber('food');

/*
|--------------------------------------------------------------------------
| Meals
|--------------------------------------------------------------------------
*/

Route::get('/meals', [
    MealController::class,
    'index',
]);

Route::post('/meals', [
    MealController::class,
    'store',
]);

Route::get('/meals/{meal}', [
    MealController::class,
    'show',
])->whereNumber('meal');

Route::put('/meals/{meal}', [
    MealController::class,
    'update',
])->whereNumber('meal');

Route::delete('/meals/{meal}', [
    MealController::class,
    'destroy',
])->whereNumber('meal');

Route::post('/meals/{meal}/items', [
    MealController::class,
    'addItem',
])->whereNumber('meal');

Route::put('/meals/{meal}/items/{item}', [
    MealController::class,
    'updateItem',
])->whereNumber('meal')->whereNumber('item');

Route::delete('/meals/{meal}/items/{item}', [
    MealController::class,
    'deleteItem',
])->whereNumber('meal')->whereNumber('item');

/*
|--------------------------------------------------------------------------
| Nutrition day, quick log, recognition
|--------------------------------------------------------------------------
*/

Route::get('/nutrition/day', [
    NutritionController::class,
    'day',
]);

Route::post('/nutrition/log', [
    NutritionController::class,
    'log',
]);

Route::post('/nutrition/recognize', [
    NutritionController::class,
    'recognize',
])->middleware('throttle:nutrition.recognize');

/*
|--------------------------------------------------------------------------
| Recipes
|--------------------------------------------------------------------------
*/

Route::get('/recipes', [
    RecipeController::class,
    'index',
]);

Route::post('/recipes', [
    RecipeController::class,
    'store',
]);

Route::get('/recipes/{recipe}', [
    RecipeController::class,
    'show',
])->whereNumber('recipe');

Route::put('/recipes/{recipe}', [
    RecipeController::class,
    'update',
])->whereNumber('recipe');

Route::delete('/recipes/{recipe}', [
    RecipeController::class,
    'destroy',
])->whereNumber('recipe');

/*
|--------------------------------------------------------------------------
| Favorites (food, meal, meditation)
|--------------------------------------------------------------------------
*/

Route::get('/favorites', [
    FavoriteController::class,
    'index',
]);

Route::post('/favorites', [
    FavoriteController::class,
    'store',
]);

Route::delete('/favorites/{favorite}', [
    FavoriteController::class,
    'destroy',
])->whereNumber('favorite');
