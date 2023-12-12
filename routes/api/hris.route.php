<?php

use App\Http\Controllers\Api\HrisController;
use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'hris', "middleware" => "checkCompayDocId"], function () {
    // Route::get('/attendance', [HrisController::class, 'index']);
    Route::group(['prefix' => 'attendance'], function () {
        Route::get('/index', [HrisController::class, 'index']);
        Route::post('/save', [HrisController::class, 'save']);
        // Route::delete('/{id}/delete', [HrisController::class, 'delete']);
        // Route::get('/{id}/detail', [HrisController::class, 'detail']);
    });
});