<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Repositories\PipelineRepository;
use App\Repositories\VariantRepository;
use Illuminate\Http\Request;

class PipelineController extends Controller
{
    protected $pipelineRepo;

    public function __construct()
    {
        $this->pipelineRepo = new PipelineRepository();
    }

    public function index(Request $request)
    {
        $filters = $request->only(['title', 'company_id']);
        return response()->json($this->pipelineRepo->index($filters));
    }

    public function save(Request $request)
    {
        return response()->json($this->pipelineRepo->save($request->all()));
    }

    public function delete($id = null)
    {
        return response()->json($this->pipelineRepo->delete($id));
    }

    public function indexStage(Request $request)
    {
        $filters = $request->only(['title', 'pipeline_id']);
        return response()->json($this->pipelineRepo->indexStage($filters));
    }

    public function saveStage(Request $request)
    {
        return response()->json($this->pipelineRepo->saveStage($request->all()));
    }

    public function deleteStage($id = null)
    {
        return response()->json($this->pipelineRepo->deleteStage($id));
    }

    public function indexDeal(Request $request)
    {
        $filters = $request->only(['stage_id']);
        return response()->json($this->pipelineRepo->indexDeal($filters));
    }

    public function saveDeal(Request $request)
    {
        return response()->json($this->pipelineRepo->saveDeal($request->all()));
    }

    public function deleteDeal($id = null)
    {
        return response()->json($this->pipelineRepo->deleteDeal($id));
    }
}