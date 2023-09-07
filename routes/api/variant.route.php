<?php

use App\Http\Controllers\Api\VariantController;
use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'variant'], function () {
    Route::get('/', [VariantController::class, 'index']);
    Route::post('/', [VariantController::class, 'save']);
    Route::delete('/{id?}/delete', [VariantController::class, 'delete']);
});