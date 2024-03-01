<?php

use App\Http\Controllers\Api\AttendanceController;
use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'hris', "middleware" => "checkCompayDocId"], function () {
    Route::group(['prefix' => 'attendance'], function () {
        Route::get('/index', [AttendanceController::class, 'index']);
        Route::post('/save', [AttendanceController::class, 'save']);
    });
});