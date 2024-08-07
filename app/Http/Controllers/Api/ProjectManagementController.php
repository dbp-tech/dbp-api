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

    public function changeCustomFieldType(Request $request)
    {
        return response()->json($this->pmRepo->changeCustomFieldType($request->all()));
    }

    public function indexPipeline(Request $request)
    {
        $filters = $request->only(["title", "pm_type_id", 'parent_id', 'is_parent']);
        return response()->json($this->pmRepo->indexPipeline($filters, $request->header('company_id')));
    }

    public function detailPipeline(Request $request, $id = null) {
        return response()->json($this->pmRepo->detailPipeline($id, $request->header('company_id')));
    }

    public function savePipeline(Request $request)
    {
        return response()->json($this->pmRepo->savePipeline($request->all(), $request->header('company_id')));
    }

    public function assignUserPipeline(Request $request)
    {
        return response()->json($this->pmRepo->assignUserPipeline($request->all()));
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

    public function indexStage(Request $request)
    {
        $filters = $request->only(['pm_type_id']);
        return response()->json($this->pmRepo->indexStage($filters, $request->header('company_id')));
    }

    public function detailStage(Request $request, $id = null) {
        return response()->json($this->pmRepo->detailStage($id, $request->header('company_id')));
    }

    public function saveStage(Request $request)
    {
        return response()->json($this->pmRepo->saveStage($request->all(), $request->header('company_id')));
    }

    public function updateBulkStage(Request $request) {
        return response()->json($this->pmRepo->updateBulkStage($request->all(), $request->header('company_id')));
    }

    public function deleteStage(Request $request, $id = null)
    {
        return response()->json($this->pmRepo->deleteStage($id, $request->header('company_id')));
    }

    public function saveDeal(Request $request)
    {
        return response()->json($this->pmRepo->saveDeal($request->all(), $request->header('company_id')));
    }

    public function changeDeal(Request $request)
    {
        return response()->json($this->pmRepo->changeDeal($request->all(), $request->header('company_id')));
    }

    public function formSubmitDeal(Request $request)
    {
        return response()->json($this->pmRepo->formSubmitDeal($request->all(), $request->header('company_id')));
    }

    public function indexDeal(Request $request)
    {
        $filters = $request->only(['pm_type_id']);
        return response()->json($this->pmRepo->indexDeal($filters, $request->header('company_id')));
    }

    public function detailDeal(Request $request, $id = null) {
        return response()->json($this->pmRepo->detailDeal($id, $request->header('company_id')));
    }

    public function deleteDeal(Request $request, $id = null)
    {
        return response()->json($this->pmRepo->deleteDeal($id, $request->header('company_id')));
    }

    public function kanbanBoardDeal(Request $request)
    {
        $filters = $request->only(['pm_pipeline_id']);
        return response()->json($this->pmRepo->kanbanBoardDeal($filters, $request->header('company_id')));
    }

    public function saveComment(Request $request)
    {
        return response()->json($this->pmRepo->saveComment($request->all(), $request->header('company_id')));
    }

    public function indexDpu(Request $request)
    {
        $filters = $request->only([]);
        return response()->json($this->pmRepo->indexDpu($filters, $request->header('company_id')));
    }

    public function saveDpu(Request $request)
    {
        return response()->json($this->pmRepo->saveDpu($request->all(), $request->header('company_id')));
    }

    public function deleteDpu(Request $request, $id = null)
    {
        return response()->json($this->pmRepo->deleteDpu($id, $request->header('company_id')));
    }

    public function indexCFM(Request $request)
    {
        $filters = $request->only([]);
        return response()->json($this->pmRepo->indexCFM($filters, $request->header('company_id')));
    }

    public function saveCFM(Request $request)
    {
        return response()->json($this->pmRepo->saveCFM($request->all(), $request->header('company_id')));
    }
}
