<?php

use App\Http\Controllers\Api\EcomController;
use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'ecom', "middleware" => "checkCompayDocId"], function () {
    Route::group(['prefix' => 'category'], function () {
        Route::post('/', [EcomController::class, 'categorySave']);
        Route::get('/', [EcomController::class, 'categoryIndex']);
        Route::delete('/{id?}/delete', [EcomController::class, 'categoryDelete']);
    });
    Route::group(['prefix' => 'product'], function () {
        Route::post('/', [EcomController::class, 'productSave']);
        Route::get('/', [EcomController::class, 'productIndex']);
        Route::delete('/{id?}/delete', [EcomController::class, 'productDelete']);
    });
});