<?php

use App\Http\Controllers\Api\CompanyAccountController;
use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'company-account', "middleware" => "checkCompayDocId"], function () {
    Route::get('/', [CompanyAccountController::class, 'index']);
    Route::post('/', [CompanyAccountController::class, 'save']);
    Route::delete('/{id?}/delete', [CompanyAccountController::class, 'delete']);
    Route::get('/list-bank', [CompanyAccountController::class, 'listBank']);
});