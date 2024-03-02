<?php

use App\Http\Controllers\Api\MarketplaceController;
use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'marketplace'], function () {
    Route::get("stores", [MarketplaceController::class , 'indexStores']);
    Route::get("orders", [MarketplaceController::class, 'indexOrders']);
    Route::get("orders/{id}", [MarketplaceController::class, 'detailOrder']);
});
