<?php

use App\Http\Controllers\Api\CompanyMasterDataStatusController;
use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'company-master-data-status', "middleware" => "checkCompayDocId"], function () {
    Route::get('/', [CompanyMasterDataStatusController::class, 'index']);
    Route::post('/', [CompanyMasterDataStatusController::class, 'save']);
    Route::delete('/{id?}/delete', [CompanyMasterDataStatusController::class, 'delete']);
});