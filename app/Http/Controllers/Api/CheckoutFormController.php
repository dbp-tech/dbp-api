<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Repositories\CheckoutFormRepository;
use App\Repositories\ProductCategoryRepository;
use Illuminate\Http\Request;

class CheckoutFormController extends Controller
{
    protected $checkoutFormRepo;

    public function __construct()
    {
        $this->checkoutFormRepo = new CheckoutFormRepository();
    }

    public function index(Request $request)
    {
        $filters = $request->only(['product_id']);
        return response()->json($this->checkoutFormRepo->index($filters));
    }

    public function save(Request $request)
    {
        return response()->json($this->checkoutFormRepo->save($request->all()));
    }

    public function delete($id = null)
    {
        return response()->json($this->checkoutFormRepo->delete($id));
    }

    public function detail($id = null)
    {
        return response()->json($this->checkoutFormRepo->detail($id));
    }

    public function detailEmbedForm($id = null)
    {
        return response()->json($this->checkoutFormRepo->detailEmbedForm($id));
    }
}