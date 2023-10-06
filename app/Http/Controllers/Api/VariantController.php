<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TestMongo;
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
        $filters = $request->only(["title", "price"]);
        return response()->json($this->variantRepo->index($filters, $request->header('company_id')));
    }

    public function save(Request $request)
    {
        return response()->json($this->variantRepo->save($request->all(), $request->header('company_id')));
    }

    public function delete(Request $request, $id = null)
    {
        return response()->json($this->variantRepo->delete($id, $request->header('company_id')));
    }

    public function testMongo()
    {
        $data = TestMongo::all();
        return response()->json($data);
    }
}