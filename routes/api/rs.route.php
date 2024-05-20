<?php

use App\Http\Controllers\Api\RestaurantController;
use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'rs', "middleware" => "checkCompayDocId"], function () {
    Route::get('/recipe', [RestaurantController::class, 'indexRecipe']);

    Route::group(['prefix' => 'category'], function () {
        Route::get('/', [RestaurantController::class, 'indexCategory']);
        Route::post('/', [RestaurantController::class, 'saveCategory']);
        Route::delete('/{id?}/delete', [RestaurantController::class, 'deleteCategory']);
        Route::get('/{id?}/detail', [RestaurantController::class, 'detailCategory']);
    });
    Route::group(['prefix' => 'menu'], function () {
        Route::get('/', [RestaurantController::class, 'indexMenu']);
        Route::post('/', [RestaurantController::class, 'saveMenu']);
        Route::delete('/{id?}/delete', [RestaurantController::class, 'deleteMenu']);
        Route::get('/{id?}/detail', [RestaurantController::class, 'detailMenu']);

        Route::group(['prefix' => 'addons'], function () {
            Route::get('/', [RestaurantController::class, 'indexMenuAddons']);
            Route::post('/', [RestaurantController::class, 'saveMenuAddons']);
            Route::delete('/{id?}/delete', [RestaurantController::class, 'deleteMenuAddons']);
            Route::get('/{id?}/detail', [RestaurantController::class, 'detailMenuAddons']);
        });

        Route::group(['prefix' => 'outlet'], function () {
            Route::post('/', [RestaurantController::class, 'saveMenuOutlet']);
            Route::post('/all-menu-per-outlet', [RestaurantController::class, 'saveMenuOutletPerOutlet']);
            Route::get('/{id?}/detail', [RestaurantController::class, 'detailMenuOutlet']);
        });
    });
    Route::group(['prefix' => 'outlet'], function () {
        Route::get('/', [RestaurantController::class, 'indexOutlet']);
        Route::post('/', [RestaurantController::class, 'saveOutlet']);
        Route::delete('/{id?}/delete', [RestaurantController::class, 'deleteOutlet']);
        Route::get('/{id?}/detail', [RestaurantController::class, 'detailOutlet']);
    });
    Route::group(['prefix' => 'order'], function () {
        Route::post('/', [RestaurantController::class, 'saveOrder']);
        Route::get('/', [RestaurantController::class, 'indexOrder']);
        Route::get('/count-order', [RestaurantController::class, 'countOrder']);
        Route::get('/all-menu-order', [RestaurantController::class, 'allMenuOrder']);
    });
    Route::get('/last-week', [RestaurantController::class, 'lastWeekOrder']);

    Route::group(['prefix' => 'addons-category'], function () {
        Route::get('/', [RestaurantController::class, 'indexAddonsCategory']);
        Route::post('/', [RestaurantController::class, 'saveAddonsCategory']);
        Route::delete('/{id?}/delete', [RestaurantController::class, 'deleteAddonsCategory']);
        Route::get('/{id?}/detail', [RestaurantController::class, 'detailAddonsCategory']);
    });
    Route::group(['prefix' => 'station'], function () {
        Route::get('/', [RestaurantController::class, 'indexStation']);
        Route::post('/', [RestaurantController::class, 'saveStation']);
        Route::delete('/{id?}/delete', [RestaurantController::class, 'deleteStation']);
        Route::get('/{id?}/detail', [RestaurantController::class, 'detailStation']);
        Route::post('/assign-to-menu', [RestaurantController::class, 'assignMenuToStation']);
        Route::post('/assign-to-outlet', [RestaurantController::class, 'assignOutletToStation']);
    });
});