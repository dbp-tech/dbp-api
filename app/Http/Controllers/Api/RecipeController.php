<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Recipe;
use Illuminate\Http\Request;

class RecipeController extends Controller {
    public function index(Request $request) {
        $recipes = Recipe::with([]);
        $recipes = $recipes->where('company_id', $request->header('company_id'));
        $recipes = $recipes->get();
        return $recipes;
    }
}