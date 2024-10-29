<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Repositories\MasterTagRepository;
use Illuminate\Http\Request;

class MasterTagController extends Controller
{
    protected $variantRepo;
    /**
     * @var MasterTagRepository()
     */
    private $masterTagRepo;

    public function __construct()
    {
        $this->masterTagRepo = new MasterTagRepository();
    }

    public function index(Request $request): \Illuminate\Http\JsonResponse
    {
        $filters = $request->only(["title"]);
        return response()->json($this->masterTagRepo->index($filters, $request->header('company_id')));
    }

    public function save(Request $request): \Illuminate\Http\JsonResponse
    {
        return response()->json($this->masterTagRepo->save($request->all(), $request->header('company_id')));
    }

    public function delete(Request $request, $id = null): \Illuminate\Http\JsonResponse
    {
        return response()->json($this->masterTagRepo->delete($id, $request->header('company_id')));
    }

    public function submitTag(Request $request): \Illuminate\Http\JsonResponse
    {
        return response()->json($this->masterTagRepo->submitTag($request->all(), $request->header('company_id')));
    }

}
