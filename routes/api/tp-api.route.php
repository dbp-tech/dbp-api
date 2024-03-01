<?php

use App\Http\Controllers\Api\TokopediaApiController;
use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'tp-api'], function () {
    Route::group(['prefix' => 'webhook'], function () {
        Route::post('/order-notification-manual', [TokopediaApiController::class, 'webhookOrderNotificationManual']);
        Route::post('/order-notification', [TokopediaApiController::class, 'webhookOrderNotification']);
        Route::post('/order-status-manual', [TokopediaApiController::class, 'webhookOrderStatusManual']);
        Route::post('/order-status', [TokopediaApiController::class, 'webhookOrderStatus']);
    });
    Route::get('/index-category', [TokopediaApiController::class, 'indexCategory']);
    Route::get('/get-shop-info', [TokopediaApiController::class, 'getShopInfo']);
    Route::get('/get-showcase/{shopId}', [TokopediaApiController::class, 'getShowcase']);
    Route::group(['prefix' => 'product'], function () {
        Route::post('/create', [TokopediaApiController::class, 'createProduct']);
        Route::get('/index', [TokopediaApiController::class, 'indexProduct']);
        Route::delete('/{id}/delete', [TokopediaApiController::class, 'deleteProduct']);
        Route::get('/{id}/detail', [TokopediaApiController::class, 'detailProduct']);
    });
    Route::group(['prefix' => 'order'], function () {
        Route::get('/index', [TokopediaApiController::class, 'indexOrder']);
        Route::get('/{orderId?}/detail', [TokopediaApiController::class, 'detailOrder']);
    });

    Route::any('/test-tiktok-bulk', [TokopediaApiController::class, 'testTiktokBulk']);
});