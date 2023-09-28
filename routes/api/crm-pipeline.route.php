<?php

use App\Http\Controllers\Api\PipelineController;
use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'crm-pipeline'], function () {
    Route::get('/', [PipelineController::class, 'index']);
    Route::post('/', [PipelineController::class, 'save']);
    Route::delete('/{id?}/delete', [PipelineController::class, 'delete']);
    Route::group(['prefix' => 'stage'], function () {
        Route::get('/', [PipelineController::class, 'indexStage']);
        Route::post('/', [PipelineController::class, 'saveStage']);
        Route::delete('/{id?}/delete', [PipelineController::class, 'deleteStage']);
    });
    Route::group(['prefix' => 'deal'], function () {
        Route::get('/', [PipelineController::class, 'indexDeal']);
        Route::post('/', [PipelineController::class, 'saveDeal']);
        Route::delete('/{id?}/delete', [PipelineController::class, 'deleteDeal']);
        Route::post('/move', [PipelineController::class, 'moveDeal']);
    });
});