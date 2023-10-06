<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Repositories\ProductRepository;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    protected $productRepo;

    public function __construct()
    {
        $this->productRepo = new ProductRepository();
    }

    public function index(Request $request)
    {
        return response()->json($this->productRepo->index($request->header('company_id')));
    }

    public function save(Request $request)
    {
        return response()->json($this->productRepo->save($request->all(), $request->header('company_id')));
    }

    public function delete(Request $request, $id = null)
    {
        return response()->json($this->productRepo->delete($id, $request->header('company_id')));
    }

    public function detail(Request $request, $id = null)
    {
        return response()->json($this->productRepo->detail($id, $request->header('company_id')));
    }

    public function indexFuTemplate(Request $request)
    {
        $filters = $request->only(['product_id']);
        return response()->json($this->productRepo->indexFuTemplate($filters));
    }

    public function detailFuTemplate($id = null)
    {
        return response()->json($this->productRepo->detailFuTemplate($id));
    }

    public function saveFuTemplate(Request $request)
    {
        return response()->json($this->productRepo->saveFuTemplate($request->all()));
    }
}