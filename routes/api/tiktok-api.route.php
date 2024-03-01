<?php

use App\Http\Controllers\Api\TiktokApiController;
use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'tiktok-api'], function () {
    Route::group(['prefix' => 'order'], function () {
        Route::get('/', [TiktokApiController::class, 'orderIndex']);
        Route::get('/detail', [TiktokApiController::class, 'orderDetail']);
    });
    Route::group(['prefix' => 'webhook'], function () {
        Route::post('/order-status-manual', [TiktokApiController::class, 'webhookOrderStatusManual']);
    });
});