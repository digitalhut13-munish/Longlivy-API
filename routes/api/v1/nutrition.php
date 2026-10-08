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
]);

Route::put('/foods/{food}', [
    FoodController::class,
    'update',
]);

Route::delete('/foods/{food}', [
    FoodController::class,
    'destroy',
]);

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
]);

Route::put('/meals/{meal}', [
    MealController::class,
    'update',
]);

Route::delete('/meals/{meal}', [
    MealController::class,
    'destroy',
]);

Route::post('/meals/{meal}/items', [
    MealController::class,
    'addItem',
]);

Route::put('/meals/{meal}/items/{item}', [
    MealController::class,
    'updateItem',
]);

Route::delete('/meals/{meal}/items/{item}', [
    MealController::class,
    'deleteItem',
]);

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
]);

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
]);

Route::put('/recipes/{recipe}', [
    RecipeController::class,
    'update',
]);

Route::delete('/recipes/{recipe}', [
    RecipeController::class,
    'destroy',
]);

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
]);
