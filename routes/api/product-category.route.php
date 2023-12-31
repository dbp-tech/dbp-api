<?php

use App\Http\Controllers\Api\ProductCategoryController;
use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'product-category', "middleware" => "checkCompayDocId"], function () {
    Route::get('/', [ProductCategoryController::class, 'index']);
    Route::post('/', [ProductCategoryController::class, 'save']);
    Route::delete('/{id?}/delete', [ProductCategoryController::class, 'delete']);
});