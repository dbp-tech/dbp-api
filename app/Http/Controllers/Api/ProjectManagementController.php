<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Repositories\ProjectManagementRepository;
use Illuminate\Http\Request;

class ProjectManagementController extends Controller
{
    protected $pmRepo;

    public function __construct()
    {
        $this->pmRepo = new ProjectManagementRepository();
    }

    public function indexType(Request $request)
    {
        $filters = $request->only(["name"]);
        return response()->json($this->pmRepo->indexType($filters, $request->header('company_id')));
    }

    public function saveType(Request $request)
    {
        return response()->json($this->pmRepo->saveType($request->all(), $request->header('company_id')));
    }

    public function deleteType(Request $request, $id = null)
    {
        return response()->json($this->pmRepo->deleteType($id, $request->header('company_id')));
    }

    public function indexPipeline(Request $request)
    {
        $filters = $request->only(["title", "pm_type_id", 'parent_id', 'is_parent']);
        return response()->json($this->pmRepo->indexPipeline($filters, $request->header('company_id')));
    }

    public function savePipeline(Request $request)
    {
        return response()->json($this->pmRepo->savePipeline($request->all(), $request->header('company_id')));
    }

    public function deletePipeline(Request $request, $id = null)
    {
        return response()->json($this->pmRepo->deletePipeline($id, $request->header('company_id')));
    }

    public function indexCF(Request $request)
    {
        $filters = $request->only([]);
        return response()->json($this->pmRepo->indexCF($filters, $request->header('company_id')));
    }

    public function saveCF(Request $request)
    {
        return response()->json($this->pmRepo->saveCF($request->all(), $request->header('company_id')));
    }

    public function deleteCF(Request $request, $id = null)
    {
        return response()->json($this->pmRepo->deleteCF($id, $request->header('company_id')));
    }
}