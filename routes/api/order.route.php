<?php

use App\Http\Controllers\Api\OrderController;
use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'order'], function () {
    Route::get('/', [OrderController::class, 'index']);
    Route::post('/', [OrderController::class, 'save']);
    Route::delete('/{id?}/delete', [OrderController::class, 'delete']);
    Route::get('/{id?}/detail', [OrderController::class, 'detail']);
});