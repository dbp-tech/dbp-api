<?php

use App\Http\Controllers\Api\ConfigurationController;
use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'configuration', "middleware" => "checkCompayDocId"], function () {
    Route::group(['prefix' => 'role'], function () {
        Route::get('/', [ConfigurationController::class, 'indexRole']);
        Route::post('/', [ConfigurationController::class, 'saveRole']);
        Route::delete('/{id?}/delete', [ConfigurationController::class, 'deleteRole']);
        Route::post('/assign-system-module', [ConfigurationController::class, 'assignSystemModuleRole']);
        Route::post('/assign-user', [ConfigurationController::class, 'assignUserRole']);
    });
    Route::group(['prefix' => 'module'], function () {
        Route::get('/', [ConfigurationController::class, 'indexModule']);
        Route::get('/by-subscription', [ConfigurationController::class, 'bySubscriptionModule']);
    });
});
