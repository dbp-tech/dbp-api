<?php

use App\Http\Controllers\Api\TiktokApiController;
use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'tiktok-api'], function () {
    Route::group(['prefix' => 'webhook'], function () {
        Route::post('/order-status-manual', [TiktokApiController::class, 'webhookOrderStatusManual']);
    });
});