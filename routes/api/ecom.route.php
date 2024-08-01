<?php

use App\Http\Controllers\Api\EcomController;
use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'ecom', "middleware" => "checkCompayDocId"], function () {
    Route::group(['prefix' => 'store'], function () {
        Route::post('/', [EcomController::class, 'storeSave']);
        Route::get('/', [EcomController::class, 'storeIndex']);
        Route::delete('/{id?}/delete', [EcomController::class, 'storeDelete']);
        Route::get('/{id?}/marketplace-detail', [EcomController::class, 'marketplaceDetail']);
        Route::get('/{id?}/marketplace-product', [EcomController::class, 'marketplaceProduct']);
        Route::post('/marketplace-product', [EcomController::class, 'storeMarketplaceProduct']);
        Route::get('/{id?}/marketplace-order', [EcomController::class, 'marketplaceOrder']);
        Route::get('/{orderId?}/marketplace-order-detail', [EcomController::class, 'marketplaceOrderDetail']);
    });
    Route::group(['prefix' => 'category'], function () {
        Route::post('/', [EcomController::class, 'categorySave']);
        Route::get('/', [EcomController::class, 'categoryIndex']);
        Route::delete('/{id?}/delete', [EcomController::class, 'categoryDelete']);
    });
    Route::group(['prefix' => 'product'], function () {
        Route::post('/', [EcomController::class, 'productSave']);
        Route::get('/', [EcomController::class, 'productIndex']);
        Route::delete('/{id?}/delete', [EcomController::class, 'productDelete']);
        Route::get('/{id?}/get-product', [EcomController::class, 'getProductOnly']);
        Route::post('/post-product/only', [EcomController::class, 'setProductOnly']);
        Route::delete('/{id?}/delete-product', [EcomController::class, 'deleteProductOnly']);
    }); 
});