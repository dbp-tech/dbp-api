<?php

use App\Http\Controllers\Api\CustomerController;
use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'customer', "middleware" => "checkCompayDocId"], function () {
    Route::post('/create-bulk', [CustomerController::class, 'createBulk']);
    Route::delete('/{id}/delete', [CustomerController::class, 'delete']);
    Route::put('/update', [CustomerController::class, 'update']);
    Route::post('/assign-recipe', [CustomerController::class, 'assignRecipe']);
    Route::post('/unassign-recipe', [CustomerController::class, 'unassignRecipe']);
    Route::get('/{id}/detail', [CustomerController::class, 'detail']);
    Route::get('/', [CustomerController::class, 'index']);
});