<?php

use App\Http\Controllers\Api\MasterTagController;
use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'master-tag', "middleware" => "checkCompayDocId"], function () {
    Route::get('/', [MasterTagController::class, 'index']);
    Route::post('/', [MasterTagController::class, 'save']);
    Route::delete('/{id?}/delete', [MasterTagController::class, 'delete']);
    Route::post('/submit-tag', [MasterTagController::class, 'submitTag']);
});
