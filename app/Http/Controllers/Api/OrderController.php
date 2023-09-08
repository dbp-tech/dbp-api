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
        return response()->json($this->orderRepo->index($filters));
    }

    public function save(Request $request)
    {
        return response()->json($this->orderRepo->save($request->all()));
    }

    public function delete($id = null)
    {
        return response()->json($this->orderRepo->delete($id));
    }

    public function detail($id = null)
    {
        return response()->json($this->orderRepo->detail($id));
    }
}