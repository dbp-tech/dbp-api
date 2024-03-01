<?php

use App\Http\Controllers\Api\ProjectManagementController;
use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'pm', "middleware" => "checkCompayDocId"], function () {
    Route::group(['prefix' => 'type'], function () {
        Route::get('/', [ProjectManagementController::class, 'indexType']);
        Route::post('/', [ProjectManagementController::class, 'saveType']);
        Route::delete('/{id?}/delete', [ProjectManagementController::class, 'deleteType']);
        Route::post('/change-custom-field', [ProjectManagementController::class, 'changeCustomFieldType']);
    });
    Route::group(['prefix' => 'pipeline'], function () {
        Route::get('/', [ProjectManagementController::class, 'indexPipeline']);
        Route::post('/', [ProjectManagementController::class, 'savePipeline']);
        Route::get('/{id?}/detail', [ProjectManagementController::class, 'detailPipeline']);
        Route::delete('/{id?}/delete', [ProjectManagementController::class, 'deletePipeline']);
    });
    Route::group(['prefix' => 'custom-field'], function () {
        Route::get('/', [ProjectManagementController::class, 'indexCF']);
        Route::post('/', [ProjectManagementController::class, 'saveCF']);
        Route::delete('/{id?}/delete', [ProjectManagementController::class, 'deleteCF']);
    });
    Route::group(['prefix' => 'stage'], function () {
        Route::get('/', [ProjectManagementController::class, 'indexStage']);
        Route::post('/', [ProjectManagementController::class, 'saveStage']);
        Route::post('/update', [ProjectManagementController::class , 'updateBulkStage']);
        Route::get('/{id?}/detail', [ProjectManagementController::class, 'detailStage']);
        Route::delete('/{id?}/delete', [ProjectManagementController::class, 'deleteStage']);
    });
    Route::group(['prefix' => 'deal'], function () {
        Route::get('/', [ProjectManagementController::class, 'indexDeal']);
        Route::post('/', [ProjectManagementController::class, 'saveDeal']);
        Route::post('/change', [ProjectManagementController::class, 'changeDeal']);
        Route::delete('/{id?}/delete', [ProjectManagementController::class, 'deleteDeal']);
        Route::get('/{id?}/detail', [ProjectManagementController::class, 'detailDeal']);
        Route::get('/kanban-board', [ProjectManagementController::class, 'kanbanBoardDeal']);
    });
});
