<?php

use App\Http\Controllers\Api\RestaurantController;
use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'rs', "middleware" => "checkCompayDocId"], function () {
    Route::get('/recipe', [RestaurantController::class, 'indexRecipe']);

    Route::group(['prefix' => 'category'], function () {
        Route::get('/', [RestaurantController::class, 'indexCategory']);
        Route::post('/', [RestaurantController::class, 'saveCategory']);
        Route::delete('/{id?}/delete', [RestaurantController::class, 'deleteCategory']);
        Route::get('/{id?}/detail', [RestaurantController::class, 'detailCategory']);
    });
    Route::group(['prefix' => 'menu'], function () {
        Route::get('/', [RestaurantController::class, 'indexMenu']);
        Route::post('/', [RestaurantController::class, 'saveMenu']);
        Route::delete('/{id?}/delete', [RestaurantController::class, 'deleteMenu']);
        Route::get('/{id?}/detail', [RestaurantController::class, 'detailMenu']);
    });
    Route::group(['prefix' => 'outlet'], function () {
        Route::get('/', [RestaurantController::class, 'indexOutlet']);
        Route::post('/', [RestaurantController::class, 'saveOutlet']);
        Route::delete('/{id?}/delete', [RestaurantController::class, 'deleteOutlet']);
        Route::get('/{id?}/detail', [RestaurantController::class, 'detailOutlet']);
    });
});