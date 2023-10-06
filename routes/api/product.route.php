<?php

use App\Http\Controllers\Api\ProductController;
use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'product', "middleware" => "checkCompayDocId"], function () {
    Route::get('/', [ProductController::class, 'index']);
    Route::post('/', [ProductController::class, 'save']);
    Route::delete('/{id?}/delete', [ProductController::class, 'delete']);
    Route::get('/{id?}/detail', [ProductController::class, 'detail']);
    Route::group(['prefix' => 'fu-template'], function () {
        Route::get('/', [ProductController::class, 'indexFuTemplate']);
        Route::get('/{id?}/detail', [ProductController::class, 'detailFuTemplate']);
        Route::post('/', [ProductController::class, 'saveFuTemplate']);
    });
});