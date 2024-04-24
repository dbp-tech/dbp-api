<?php

use App\Http\Controllers\Api\RecipeController;
use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'recipe', "middleware" => "checkCompayDocId"], function () {
    Route::get('/', [RecipeController::class, 'index']);
});