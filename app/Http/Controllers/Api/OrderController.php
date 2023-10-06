<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Repositories\OrderRepository;
use App\Repositories\ProductCategoryRepository;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    protected $orderRepo;

    public function __construct()
    {
        $this->orderRepo = new OrderRepository();
    }

    public function index(Request $request)
    {
        $filters = $request->only([]);
        return response()->json($this->orderRepo->index($filters, $request->header('company_id')));
    }

    public function save(Request $request)
    {
        return response()->json($this->orderRepo->save($request->all(), $request->header('company_id')));
    }

    public function delete(Request $request, $id = null)
    {
        return response()->json($this->orderRepo->delete($id, $request->header('company_id')));
    }

    public function detail(Request $request, $id = null)
    {
        return response()->json($this->orderRepo->detail($id, $request->header('company_id')));
    }

    public function saveFuHistory(Request $request)
    {
        return response()->json($this->orderRepo->saveFuHistory($request->all()));
    }

    public function indexFuHistory($id)
    {
        return response()->json($this->orderRepo->indexFuHistory($id));
    }

    public function saveStatus(Request $request)
    {
        return response()->json($this->orderRepo->saveStatus($request->all()));
    }

    public function indexStatus($id)
    {
        return response()->json($this->orderRepo->indexStatus($id));
    }
}