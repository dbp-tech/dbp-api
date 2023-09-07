<?php

use App\Http\Controllers\Api\CheckoutFormController;
use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'checkout-form'], function () {
    Route::get('/', [CheckoutFormController::class, 'index']);
    Route::post('/', [CheckoutFormController::class, 'save']);
    Route::delete('/{id?}/delete', [CheckoutFormController::class, 'delete']);
});