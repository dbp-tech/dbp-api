<?php

use App\Http\Controllers\Api\TokopediaApiController;
use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'tp-api'], function () {
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
    });
    Route::get('/test-tiktok-bulk', [TokopediaApiController::class, 'testTiktokBulk']);
});