<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Repositories\ProductCategoryRepository;
use Illuminate\Http\Request;

class ProductCategoryController extends Controller
{
    protected $productCategoryRepo;

    public function __construct()
    {
        $this->productCategoryRepo = new ProductCategoryRepository();
    }

    public function index(Request $request)
    {
        $filters = $request->only(['title']);
        return response()->json($this->productCategoryRepo->index($filters, $request->header('company_id')));
    }

    public function save(Request $request)
    {
        return response()->json($this->productCategoryRepo->save($request->all(), $request->header('company_id')));
    }

    public function delete(Request $request, $id = null)
    {
        return response()->json($this->productCategoryRepo->delete($id, $request->header('company_id')));
    }
}