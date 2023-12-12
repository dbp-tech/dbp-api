<?php

use App\Http\Controllers\Api\AttendanceController;
use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'hris', "middleware" => "checkCompayDocId"], function () {
    // Route::get('/attendance', [AttendanceController::class, 'index']);
    Route::group(['prefix' => 'attendance'], function () {
        Route::get('/index', [AttendanceController::class, 'index']);
        Route::post('/save', [AttendanceController::class, 'save']);
        // Route::delete('/{id}/delete', [AttendanceController::class, 'delete']);
        // Route::get('/{id}/detail', [AttendanceController::class, 'detail']);
    });
});