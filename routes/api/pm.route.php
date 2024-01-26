<?php

use App\Http\Controllers\Api\ProjectManagementController;
use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'pm', "middleware" => "checkCompayDocId"], function () {
    Route::group(['prefix' => 'type'], function () {
        Route::get('/', [ProjectManagementController::class, 'indexType']);
        Route::post('/', [ProjectManagementController::class, 'saveType']);
        Route::delete('/{id?}/delete', [ProjectManagementController::class, 'deleteType']);
    });
    Route::group(['prefix' => 'pipeline'], function () {
        Route::get('/', [ProjectManagementController::class, 'indexPipeline']);
        Route::post('/', [ProjectManagementController::class, 'savePipeline']);
        Route::delete('/{id?}/delete', [ProjectManagementController::class, 'deletePipeline']);
    });
    Route::group(['prefix' => 'custom-field'], function () {
        Route::get('/', [ProjectManagementController::class, 'indexCF']);
        Route::post('/', [ProjectManagementController::class, 'saveCF']);
        Route::delete('/{id?}/delete', [ProjectManagementController::class, 'deleteCF']);
    });
});