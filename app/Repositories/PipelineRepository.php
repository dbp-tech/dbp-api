<?php

namespace App\Repositories;

use App\Models\Company;
use App\Models\CrmDeal;
use App\Models\CrmDealPipeline;
use App\Models\CrmPipeline;
use App\Models\CrmStage;
use App\Models\Customer;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class PipelineRepository
{
    public function index($filters)
    {
        $pipeline = CrmPipeline::with(['stages']);
        if (!empty($filters['title'])) {
            $pipeline = $pipeline->where('pipeline_title', 'LIKE', '%' . $filters['title'] . '%');
        }
        if (!empty($filters['company_id'])) {
            $pipeline = $pipeline->where('company_id', $filters['company_id']);
        }
        $pipeline = $pipeline->orderBy('id', 'desc')->get();
        return $pipeline;
    }

    public function save($data)
    {
        try {
            $validator = Validator::make($data, [
                'company_id' => 'required',
                'title' => 'required',
            ]);
            if ($validator->fails()) return resultFunction('Err code PR-S: validation err ' . $validator->errors());

            $company = Company::find($data['company_id']);
            if (!$company) return resultFunction('Err code PR-S: company not found');

            $pipeline = new CrmPipeline();
            $pipeline->company_id = $data['company_id'];
            $pipeline->pipeline_title = $data['title'];
            $pipeline->save();

            return resultFunction("Success to create pipeline", true, $pipeline);
        } catch (\Exception $e) {
            return resultFunction("Err code PR-S catch: " . $e->getMessage());
        }
    }

    public function delete($id) {
        try {
            $pipeline =  CrmPipeline::find($id);
            if (!$pipeline) return resultFunction('Err PR-D: pipeline not found');
            $pipeline->delete();

            return resultFunction("Success to delete pipeline", true);
        } catch (\Exception $e) {
            return resultFunction("Err code PR-D catch: " . $e->getMessage());
        }
    }

    public function indexStage($filters)
    {
        $stage = CrmStage::with(['pipeline']);
        if (!empty($filters['title'])) {
            $stage = $stage->where('stage_title', 'LIKE', '%' . $filters['title'] . '%');
        }
        if (!empty($filters['pipeline_id'])) {
            $stage = $stage->where('pipeline_id', $filters['pipeline_id']);
        }
        $stage = $stage->orderBy('id', 'desc')->paginate(25);
        return $stage;
    }

    public function saveStage($data)
    {
        try {
            $validator = Validator::make($data, [
                'pipeline_id' => 'required',
                'title' => 'required',
            ]);
            if ($validator->fails()) return resultFunction('Err code PR-S: validation err ' . $validator->errors());

            $pipeline = CrmPipeline::find($data['pipeline_id']);
            if (!$pipeline) return resultFunction('Err code PR-S: pipeline not found');

            $stage = new CrmStage();
            $stage->pipeline_id = $data['pipeline_id'];
            $stage->pipeline_index = $data['pipeline_index'];
            $stage->stage_title = $data['title'];
            $stage->save();

            return resultFunction("Success to create stage", true, $stage);
        } catch (\Exception $e) {
            return resultFunction("Err code PR-S catch: " . $e->getMessage());
        }
    }

    public function deleteStage($id) {
        try {
            $stage =  CrmStage::find($id);
            if (!$stage) return resultFunction('Err PR-D: stage not found');
            $stage->delete();

            return resultFunction("Success to delete stage", true);
        } catch (\Exception $e) {
            return resultFunction("Err code PR-D catch: " . $e->getMessage());
        }
    }

    public function saveDeal($data)
    {
        try {
            DB::beginTransaction();
            $validator = Validator::make($data, [
                'pipeline_id' => 'required',
                'stage_id' => 'required',
                'deal_customer' => 'required',
                'title' => 'required',
            ]);
            if ($validator->fails()) return resultFunction('Err code PR-S: validation err ' . $validator->errors());

            $pipeline = CrmPipeline::find($data['pipeline_id']);
            if (!$pipeline) return resultFunction('Err code PR-S: pipeline not found');

            $stage = CrmStage::find($data['stage_id']);
            if (!$stage) return resultFunction('Err code PR-S: stage not found');

            $customer = Customer::find($data['deal_customer']);
            if (!$customer) return resultFunction('Err code PR-S: customer not found');

            $deal = new CrmDeal();
            $deal->deal_customer = $data['deal_customer'];
            $deal->deal_title = $data['title'];
            $deal->save();

            $dealPipeline = new CrmDealPipeline();
            $dealPipeline->deal_id = $deal->id;
            $dealPipeline->pipeline_id = $pipeline->id;
            $dealPipeline->stage_id = $stage->id;
            $dealPipeline->save();

            DB::commit();
            return resultFunction("Success to create deal", true, $deal);
        } catch (\Exception $e) {
            return resultFunction("Err code PR-S catch: " . $e->getMessage());
        }
    }

    public function indexDeal($filters)
    {
        $deals = CrmDeal::with(['deal_pipeline.stage']);
        if (!empty($filters['stage_id'])) {
            $stageId =  explode(",", $filters['stage_id']);
            $deals = $deals->whereHas('deal_pipeline', function ($q) use ($stageId) {
                $q->whereIn('stage_id', $stageId);
            });
        }
        $deals = $deals->orderBy('id', 'desc')->get();
        return $deals;
    }

    public function deleteDeal($id) {
        try {
            DB::beginTransaction();
            $deal =  CrmDeal::find($id);
            if (!$deal) return resultFunction('Err PR-D: deal not found');
            $deal->delete();
            CrmDealPipeline::where('deal_id', $id)->delete();

            DB::commit();
            return resultFunction("Success to delete deals", true);
        } catch (\Exception $e) {
            return resultFunction("Err code PR-D catch: " . $e->getMessage());
        }
    }
}