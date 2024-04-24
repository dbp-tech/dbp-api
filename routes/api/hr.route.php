<?php

use App\Http\Controllers\Api\AttendanceController;
use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'hr', "middleware" => "checkCompayDocId"], function () {
    Route::group(['prefix' => 'employee'], function () {
        Route::get('/', [AttendanceController::class, 'employeeIndex']);
        Route::post('/', [AttendanceController::class, 'employeeSave']);
    });

    Route::group(['prefix' => 'attendance'], function () {
        Route::get('/', [AttendanceController::class, 'attendanceIndex']);
        Route::post('/', [AttendanceController::class, 'attendanceSave'])->middleware('checkUserUid');
        Route::get('/{id}/detail', [AttendanceController::class, 'attendanceDetail']);

        Route::group(['prefix' => 'detail', 'middleware' => 'checkUserUid'], function () {
            Route::post('/', [AttendanceController::class, 'attendanceDetailSave']);
            Route::delete('/{attendanceId}/{attendanceDetailId}/delete', [AttendanceController::class, 'attendanceDetailDelete']);
        });
    });

    Route::group(['prefix' => 'company-setting'], function () {
        Route::get('/', [AttendanceController::class, 'companySettingIndex']);
        Route::post('/', [AttendanceController::class, 'companySettingSave']);
        Route::delete('/{id?}/delete', [AttendanceController::class, 'companySettingDelete']);
    });

    Route::group(['prefix' => 'shift'], function () {
        Route::get('/', [AttendanceController::class, 'shiftIndex']);
        Route::post('/', [AttendanceController::class, 'shiftSave']);
        Route::delete('/{id?}/delete', [AttendanceController::class, 'shiftDelete']);
    });

    Route::group(['prefix' => 'schedule'], function () {
        Route::get('/', [AttendanceController::class, 'scheduleIndex']);
        Route::post('/', [AttendanceController::class, 'scheduleSave']);
        Route::delete('/{id?}/delete', [AttendanceController::class, 'scheduleDelete']);
    });

    Route::group(['prefix' => 'schedule-rotation'], function () {
        Route::get('/', [AttendanceController::class, 'scheduleRotationIndex']);
        Route::post('/', [AttendanceController::class, 'scheduleRotationSave']);
        Route::delete('/{id?}/delete', [AttendanceController::class, 'scheduleRotationDelete']);
    });

    Route::group(['prefix' => 'employee-schedule'], function () {
        Route::post('/', [AttendanceController::class, 'employeeScheduleSave']);
    });
});