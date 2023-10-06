<?php

use App\Http\Controllers\Api\OrderController;
use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'order', "middleware" => "checkCompayDocId"], function () {
    Route::get('/', [OrderController::class, 'index']);
    Route::post('/', [OrderController::class, 'save']);
    Route::delete('/{id?}/delete', [OrderController::class, 'delete']);
    Route::get('/{id?}/detail', [OrderController::class, 'detail']);

    Route::group(['prefix' => 'fu-history'], function () {
        Route::post('/', [OrderController::class, 'saveFuHistory']);
        Route::get('/{id}', [OrderController::class, 'indexFuHistory']);
    });

    Route::group(['prefix' => 'status'], function () {
        Route::post('/', [OrderController::class, 'saveStatus']);
        Route::get('/{id}', [OrderController::class, 'indexStatus']);
    });
});