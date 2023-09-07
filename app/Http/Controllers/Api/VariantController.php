<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Repositories\VariantRepository;
use Illuminate\Http\Request;

class VariantController extends Controller
{
    protected $variantRepo;

    public function __construct()
    {
        $this->variantRepo = new VariantRepository();
    }

    public function index(Request $request)
    {
        $filters = $request->only(['title', 'company_id', "title", "price"]);
        return response()->json($this->variantRepo->index($filters));
    }

    public function save(Request $request)
    {
        return response()->json($this->variantRepo->save($request->all()));
    }

    public function delete($id = null)
    {
        return response()->json($this->variantRepo->delete($id));
    }
}