<?php

namespace App\Repositories;

use App\Models\Company;
use App\Models\PmCustomField;
use App\Models\PmDeal;
use App\Models\PmDealCustomField;
use App\Models\PmDealProgress;
use App\Models\PmPipeline;
use App\Models\PmPipelineCustomField;
use App\Models\PmStage;
use App\Models\PmStageCustomField;
use App\Models\PmType;
use App\Models\PmTypeCustomField;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ProjectManagementRepository
{
    public function indexType($filters, $companyId)
    {
        $pmType = PmType::with(['pm_type_custom_fields.pm_custom_field']);
        if (!empty($filters['name'])) {
            $pmType = $pmType->where('name', 'LIKE', '%' . $filters['name'] . '%');
        }
        $pmType = $pmType->where('company_id', $companyId);
        $pmType = $pmType->orderBy('id', 'desc')->paginate(25);
        return $pmType;
    }

    public function saveType($data, $companyId)
    {
        try {
            $validator = Validator::make($data, [
                'name' => 'required',
            ]);
            if ($validator->fails()) return resultFunction('Err code PMR-S: validation err ' . $validator->errors());

            $company = Company::find($companyId);
            if (!$company) return resultFunction('Err code PMR-S: company not found');

            if ($data['id']) {
                $pmType = PmType::find($data['id']);
                if (!$pmType) return resultFunction('Err code PMR-S: type not found');
            } else {
                $pmType = new PmType();
            }
            $pmType->company_id = $company->id;
            $pmType->name = $data['name'];
            $pmType->save();

            return resultFunction("Success to create type", true, $pmType);
        } catch (\Exception $e) {
            return resultFunction("Err code PMR-S catch: " . $e->getMessage());
        }
    }

    public function deleteType($id, $companyId) {
        try {
            $pmType =  PmType::find($id);
            if (!$pmType) return resultFunction('Err PMR-D: product type not found');

            if ($pmType->company_id != $companyId) return resultFunction('Err PMR-D: type not found');
            $pmType->delete();

            return resultFunction("Success to delete type", true);
        } catch (\Exception $e) {
            return resultFunction("Err code PMR-D catch: " . $e->getMessage());
        }
    }

    public function changeCustomFieldType($data)
    {
        try {
            $validator = Validator::make($data, [
                'pm_type_id' => 'required',
                'pm_custom_field_ids' => 'required'
            ]);
            if ($validator->fails()) return resultFunction('Err code PMR-S: validation err ' . $validator->errors());

            $pmType = PmType::find($data['pm_type_id']);
            if (!$pmType) return resultFunction('Err code PMR-S: type not found');

            $pmCustomFields = PmCustomField::with([])
                ->whereIn('id', $data['pm_custom_field_ids'])
                ->get();
            if (count($pmCustomFields) !== count($data['pm_custom_field_ids'])) return resultFunction('Err code PMR-S: custom field not match');

            PmTypeCustomField::where('pm_type_id', $data['pm_type_id'])->delete();
            $pmTypeCustomFieldInput = [];
            foreach ($data['pm_custom_field_ids'] as $datum) {
                $pmTypeCustomFieldInput[] = [
                    'pm_type_id' => $data['pm_type_id'],
                    'pm_custom_field_id' => $datum,
                    'createdAt' => date("Y-m-d H:i:s"),
                    'updatedAt' => date("Y-m-d H:i:s")
                ];
            }
            if (count($pmTypeCustomFieldInput) > 0) {
                PmTypeCustomField::insert($pmTypeCustomFieldInput);
            }


            $pmType = PmType::with(['pm_type_custom_fields.pm_custom_field'])->find($data['pm_type_id']);

            return resultFunction("Success to change custom field type", true, $pmType);
        } catch (\Exception $e) {
            return resultFunction("Err code PMR-S catch: " . $e->getMessage());
        }
    }

    public function savePipeline($data, $companyId)
    {
        try {
            DB::beginTransaction();

            $validator = Validator::make($data, [
                'pm_type_id' => 'required',
                'title' => 'required',
            ]);
            if ($validator->fails()) return resultFunction('Err code PMR-S: validation err ' . $validator->errors());

            $company = Company::find($companyId);
            if (!$company) return resultFunction('Err code PMR-S: company not found');

            $pmType = PmType::find($data['pm_type_id']);
            if (!$pmType) return resultFunction('Err code PMR-S: type not found');

            if ($data['id']) {
                $pmPipeline = PmPipeline::find($data['id']);
            } else {
                $pmPipeline = new PmPipeline();
            }

            if ($data['parent_id']) {
                $parentPipeline = PmPipeline::find($data['parent_id']);
                if (!$parentPipeline) return resultFunction('Err code PMR-S: parent of pipeline not found');

                if ($data['id']) {
                    if ($pmPipeline->parent_id) {
                        if ($pmPipeline->parent_id !== $data['parent_id']) {
                            $oldPipeline = PmPipeline::find($pmPipeline->parent_id);
                            if ($oldPipeline) {
                                $hasChildPipeline = PmPipeline::with([])
                                    ->where('parent_id', $oldPipeline->id)
                                    ->where('id', '<>', $pmPipeline->id)
                                    ->count();
                                $oldPipeline->is_parent = $hasChildPipeline > 0 ? 1 : 0;
                                $oldPipeline->save();
                            }
                        }
                        $parentPipeline->is_parent = 1;
                    }
                } else {
                    $parentPipeline->is_parent = 1;
                }
                $parentPipeline->save();
            }

            $cfs = PmCustomField::whereIn('id', $data['custom_fields'])->get();
            if (count($cfs) !==  count($data['custom_fields'])) return resultFunction('Err code PMR-S: count of custom fields is not same between request params and database');

            $pmPipeline->company_id = $company->id;
            $pmPipeline->pm_type_id = $data['pm_type_id'];
            $pmPipeline->parent_id = $data['parent_id'];
            $pmPipeline->is_parent = 0;
            $pmPipeline->title = $data['title'];
            $pmPipeline->save();

            DB::commit();
            return resultFunction("Success to create pipeline", true, $pmPipeline);
        } catch (\Exception $e) {
            return resultFunction("Err code PMR-S catch: " . $e->getMessage());
        }
    }

    public function indexPipeline($filters, $companyId)
    {
        $pmPipeline = PmPipeline::with(['pm_type.pm_type_custom_fields.pm_custom_field', 'pm_stages']);
        if (in_array($filters['is_parent'], [0, 1])) {
            $pmPipeline = $pmPipeline->where('is_parent', $filters['is_parent']);
        }
        if (!empty($filters['pm_type_id'])) {
            $pmPipeline = $pmPipeline->where('pm_type_id', $filters['pm_type_id']);
        }
        if (!empty($filters['parent_id'])) {
            $pmPipeline = $pmPipeline->where('parent_id', $filters['parent_id']);
        }
        if (!empty($filters['title'])) {
            $pmPipeline = $pmPipeline->where('title', 'LIKE', '%' . $filters['title'] . '%');
        }
        $pmPipeline = $pmPipeline->where('company_id', $companyId);
        $pmPipeline = $pmPipeline->orderBy('id', 'desc')->paginate(25);
        return $pmPipeline;
    }

    public function detailPipeline($id, $companyId) {
        try {
            $pmPipeline = PmPipeline::with(['pm_type', 'pm_type.pm_type_custom_fields', 'pm_type.pm_type_custom_fields.pm_custom_field',  'pm_stages', 'pm_stages'])->find($id);
            if (!$pmPipeline) return resultFunction('Err PMR-DP: pipeline not found');

            if ($pmPipeline->company_id != $companyId) return resultFunction('Err PMR-DP: pipeline not found');

            return resultFunction("Success to get detail Pipeline", true, $pmPipeline);
        } catch (\Exception $e) {
            return resultFunction("Err code PMR-DP catch: " . $e->getMessage());
        }
    }

    public function deletePipeline($id, $companyId) {
        try {
            $pmPipeline =  PmPipeline::find($id);
            if (!$pmPipeline) return resultFunction('Err PMR-D: pipeline not found');

            if ($pmPipeline->company_id != $companyId) return resultFunction('Err PMR-D: pipeline not found');

            $hasChild = PmPipeline::with([])
                ->where('parent_id', $pmPipeline->id)
                ->count();
            if ($hasChild) return resultFunction('Err PMR-D: pipeline has child of pipeline');

            if ($pmPipeline->parent_id) {
                $oldPipeline = PmPipeline::find($pmPipeline->parent_id);
                if ($oldPipeline) {
                    $hasChildPipeline = PmPipeline::with([])
                        ->where('parent_id', $oldPipeline->id)
                        ->where('id', '<>', $pmPipeline->id)
                        ->count();
                    $oldPipeline->is_parent = $hasChildPipeline > 0 ? 1 : 0;
                    $oldPipeline->save();
                }
            }

            PmPipelineCustomField::where('pm_pipeline_id', $pmPipeline->id)->delete();
            $pmPipeline->delete();

            return resultFunction("Success to delete type", true);
        } catch (\Exception $e) {
            return resultFunction("Err code PMR-D catch: " . $e->getMessage());
        }
    }

    public function saveCF($data, $companyId)
    {
        try {
            $validator = Validator::make($data, [
                'type' => 'required',
                'label' => 'required',
                'is_required' => 'required',
            ]);
            if ($validator->fails()) return resultFunction('Err code PMR-S: validation err ' . $validator->errors());

            $company = Company::find($companyId);
            if (!$company) return resultFunction('Err code PMR-S: company not found');

            if ($data['id']) {
                $pmCF = PmCustomField::find($data['id']);
            } else {
                $pmCF = new PmCustomField();
            }

            if ($data['type'] === 'input' AND !$data['input_type']) return resultFunction('Err code PMR-S: input type has not value, please choose text or number, or email.');

            if ($data['type'] !== 'input' AND count($data['option_default']) === 0) return resultFunction('Err code PMR-S: option is empty');

            $pmCF->company_id = $company->id;
            $pmCF->type = $data['type'];
            $pmCF->label = $data['label'];
            $pmCF->input_type = $data['input_type'];
            $pmCF->is_required = $data['is_required'];
            $pmCF->option_default = $data['option_default'] === '[]' ? $data['option_default'] : json_encode($data['option_default']);
            $pmCF->save();

            return resultFunction("Success to create custom field", true, $pmCF);
        } catch (\Exception $e) {
            return resultFunction("Err code PMR-S catch: " . $e->getMessage());
        }
    }

    public function indexCF($filters, $companyId)
    {
        $pmCF = PmCustomField::with([]);
        $pmCF = $pmCF->where('company_id', $companyId);
        $pmCF = $pmCF->orderBy('id', 'desc')->paginate(25);
        return $pmCF;
    }

    public function deleteCF($id, $companyId) {
        try {
            $pmCF =  PmCustomField::find($id);
            if (!$pmCF) return resultFunction('Err PMR-D: custom field not found');

            if ($pmCF->company_id != $companyId) return resultFunction('Err PMR-D: custom field not found');

            $pmCF->delete();

            return resultFunction("Success to delete custom field", true);
        } catch (\Exception $e) {
            return resultFunction("Err code PMR-D catch: " . $e->getMessage());
        }
    }

    public function indexStage($filters, $companyId)
    {
        $pmStage = PmStage::with(['pm_type.pm_type_custom_fields.pm_custom_field', 'pm_pipeline']);
        $pmStage = $pmStage->where('company_id', $companyId);
        if (!empty($filters['pm_type_id'])) {
            $pmStage = $pmStage->where('pm_type_id', $filters['pm_type_id']);
        }
        $pmStage = $pmStage->orderBy('id', 'desc')->paginate(25);
        return $pmStage;
    }

    public function detailStage($id, $companyId) {
        try {
            $pmPipeline = PmStage::with(['pm_type', 'pm_type.pm_type_custom_fields', 'pm_pipeline'])->find($id);
            if (!$pmPipeline) return resultFunction('Err PMR-DS: Stage not found');

            if ($pmPipeline->company_id != $companyId) return resultFunction('Err PMR-DS: stage not found');

            return resultFunction("Success to get detail stage", true, $pmPipeline);
        } catch (\Exception $e) {
            return resultFunction("Err code PMR-DS catch: " . $e->getMessage());
        }
    }


    public function saveStage($data, $companyId)
    {
        try {
            DB::beginTransaction();

            $validator = Validator::make($data, [
                'pm_type_id' => 'required',
                'pm_pipeline_id' => 'required',
                'pipeline_index' => 'required',
                'title' => 'required',
            ]);
            if ($validator->fails()) return resultFunction('Err code PMR-S: validation err ' . $validator->errors());

            $company = Company::find($companyId);
            if (!$company) return resultFunction('Err code PMR-S: company not found');

            $pmPipeline = PmPipeline::find($data['pm_pipeline_id']);
            if (!$pmPipeline) return resultFunction('Err code PMR-S: pipeline not found');

            $pmType = PmType::find($data['pm_type_id']);
            if (!$pmType) return resultFunction('Err code PMR-S: type not found');

            if ($data['id']) {
                $pmStage = PmStage::find($data['id']);
            } else {
                $pmStage = new PmStage();
            }

            $pmStage->company_id = $company->id;
            $pmStage->pm_pipeline_id = $data['pm_pipeline_id'];
            $pmStage->pm_type_id = $data['pm_type_id'];
            $pmStage->pipeline_index = $data['pipeline_index'];
            $pmStage->title = $data['title'];
            $pmStage->save();

            DB::commit();
            return resultFunction("Success to create stage", true, $pmStage);
        } catch (\Exception $e) {
            return resultFunction("Err code PMR-S catch: " . $e->getMessage());
        }
    }

    public function updateBulkStage($data, $companyId) {
        try {
            DB::beginTransaction();

            $validator = Validator::make($data, [
                'pipeline_id' => 'required|exists:pm_pipelines,id',
                'stage' => 'required|array',
                'stage.*.stage_id' => 'required|exists:pm_stages,id',
                'stage.*.index' => 'required|integer'
            ]);

            if ($validator->fails()) return resultFunction('Err code PMR-UBS: validation err ' . $validator->errors());

            // Check index must be unique
            $checkIndex = collect($data["stage"])->pluck('index');
            if($checkIndex->count() != $checkIndex->unique()->count()) return resultFunction('Err code PMR-UBS: Index must be unique');


            // Check if the indices are sequential without any gaps
            $checkSortIndex = collect($data["stage"])->pluck('index')->sort()->values();
            if( $checkSortIndex->count() === $checkSortIndex->unique()->count() && $checkSortIndex->count() === $checkSortIndex->last() && $checkSortIndex->first() === 1) {

                $pipelineId = $data["pipeline_id"];
                collect($data["stage"])->each(function($q) use($companyId, $pipelineId) {
                    $pmStage = PmStage::where([
                        ['id', $q["stage_id"]],
                        ['pm_pipeline_id', $pipelineId],
                        ['company_id', $companyId]
                    ])
                    ->first();

                    if (!$pmStage) return resultFunction('Err PMR-UBS: stage not found');
                });

                foreach($data["stage"] as $stage) {
                    PmStage::where("id", $stage["stage_id"])->update(['pipeline_index' => $stage["index"]]);
                }

                DB::commit();

                return resultFunction("Success to update stage", true);
            }
            return resultFunction('Err code PMR-UBS: Index must be sort');
        } catch (\Exception $e) {
            DB::rollBack();
            return resultFunction("Err code PMR-UBS catch: " . $e->getMessage());
        }
    }

    public function deleteStage($id, $companyId) {
        try {
            DB::beginTransaction();
            $pmStage =  PmStage::find($id);
            if (!$pmStage) return resultFunction('Err PMR-D: stage not found');

            if ($pmStage->company_id != $companyId) return resultFunction('Err PMR-D: stage not found');
            $pmStage->delete();

            DB::commit();
            return resultFunction("Success to delete type", true);
        } catch (\Exception $e) {
            return resultFunction("Err code PMR-D catch: " . $e->getMessage());
        }
    }

    public function saveDeal($data, $companyId)
    {
        try {
            DB::beginTransaction();

            $validator = Validator::make($data, [
                'pm_type_id' => 'required',
                'title' => 'required',
                'pm_stage_id' => 'required',
                'pm_pipeline_id' => 'required',
            ]);
            if ($validator->fails()) return resultFunction('Err code PMR-S: validation err ' . $validator->errors());

            $company = Company::find($companyId);
            if (!$company) return resultFunction('Err code PMR-S: company not found');

            $pmStage = PmStage::find($data['pm_stage_id']);
            if (!$pmStage) return resultFunction('Err code PMR-S: sage not found');

            $pmPipeline = PmPipeline::find($data['pm_pipeline_id']);
            if (!$pmPipeline) return resultFunction('Err code PMR-S: pipeline not found');

            if ($data['id']) {
                $pmDeal = PmDeal::find($data['id']);
                if (!$pmDeal) return resultFunction('Err code PMR-S: deal not found');
            } else {
                $pmDeal = new PmDeal();
            }

            $pmDeal->company_id = $company->id;
            $pmDeal->pm_type_id = $data['pm_type_id'];
            $pmDeal->title = $data['title'];
            $pmDeal->save();

            if (!$data['id']) {
                $pmDealProgress = new PmDealProgress();
                $pmDealProgress->company_id =  $companyId;
                $pmDealProgress->pm_deal_id = $pmDeal->id;
                $pmDealProgress->pm_stage_id = $data['pm_stage_id'];
                $pmDealProgress->pm_pipeline_id = $data['pm_pipeline_id'];
                $pmDealProgress->save();
            }

            DB::commit();
            return resultFunction("Success to create deal", true, $pmStage);
        } catch (\Exception $e) {
            return resultFunction("Err code PMR-S catch: " . $e->getMessage());
        }
    }

    public function changeDeal($data, $companyId)
    {
        try {
            DB::beginTransaction();

            $validator = Validator::make($data, [
                'id' => 'required',
                'pm_stage_id' => 'required',
                'pm_pipeline_id' => 'required',
            ]);
            if ($validator->fails()) return resultFunction('Err code PMR-S: validation err ' . $validator->errors());

            $company = Company::find($companyId);
            if (!$company) return resultFunction('Err code PMR-S: company not found');

            $pmStage = PmStage::find($data['pm_stage_id']);
            if (!$pmStage) return resultFunction('Err code PMR-S: stage not found');

            $pmPipeline = PmPipeline::find($data['pm_pipeline_id']);
            if (!$pmPipeline) return resultFunction('Err code PMR-S: pipeline not found');

            $pmDeal = PmDeal::find($data['id']);
            if (!$pmDeal) return resultFunction('Err code PMR-S: deal not found');

            PmDealProgress::where('pm_deal_id', $pmDeal->id)->delete();

            $pmDealProgress = new PmDealProgress();
            $pmDealProgress->company_id =  $companyId;
            $pmDealProgress->pm_deal_id = $pmDeal->id;
            $pmDealProgress->pm_stage_id = $data['pm_stage_id'];
            $pmDealProgress->pm_pipeline_id = $data['pm_pipeline_id'];
            $pmDealProgress->save();

            DB::commit();
            return resultFunction("Success to change deal", true, $pmStage);
        } catch (\Exception $e) {
            return resultFunction("Err code PMR-S catch: " . $e->getMessage());
        }
    }

    public function indexDeal($filters, $companyId)
    {
        $pmDeal = PmDeal::with(['pm_type.pm_type_custom_fields.pm_custom_field', 'pm_deal_progress.pm_pipeline', 'pm_deal_progress.pm_stage']);
        $pmDeal = $pmDeal->where('company_id', $companyId);
        if (!empty($filters['pm_type_id'])) {
            $pmDeal = $pmDeal->where('pm_type_id', $filters['pm_type_id']);
        }
        $pmDeal = $pmDeal->orderBy('id', 'desc')->paginate(25);
        return $pmDeal;
    }

    public function detailDeal($id, $companyId) {
        try {
            $pmPipeline = PmDeal::with(['pm_type.pm_type_custom_fields.pm_custom_field', 'pm_deal_progress.pm_pipeline', 'pm_deal_progress.pm_stage'])->find($id);
            if (!$pmPipeline) return resultFunction('Err PMR-DD: deal not found');

            if ($pmPipeline->company_id != $companyId) return resultFunction('Err PMR-DS: deal not found');

            return resultFunction("Success to get detail deal", true, $pmPipeline);
        } catch (\Exception $e) {
            return resultFunction("Err code PMR-DD catch: " . $e->getMessage());
        }
    }

    public function deleteDeal($id, $companyId) {
        try {
            DB::beginTransaction();
            $pmDeal =  PmDeal::find($id);
            if (!$pmDeal) return resultFunction('Err PMR-D: deal not found');

            if ($pmDeal->company_id != $companyId) return resultFunction('Err PMR-D: deal not found');

            PmDealProgress::where('pm_deal_id', $pmDeal->id)->delete();
            $pmDeal->delete();

            DB::commit();
            return resultFunction("Success to delete type", true);
        } catch (\Exception $e) {
            return resultFunction("Err code PMR-D catch: " . $e->getMessage());
        }
    }

    public function kanbanBoardDeal($filters, $companyId)
    {
        $pmPipeline = PmPipeline::find($filters['pm_pipeline_id']);

        $pmDealProgress = PmDealProgress::with(['pm_stage', 'pm_deal']);
        $pmDealProgress = $pmDealProgress->where('company_id', $companyId);
        $pmDealProgress = $pmDealProgress->where('pm_pipeline_id', $filters['pm_pipeline_id']);
        $pmDealProgress = $pmDealProgress->get();

        $pmStages = PmStage::with([])
            ->where('pm_pipeline_id', $filters['pm_pipeline_id'])
            ->orderBy('pm_pipeline_id')
            ->get();

        $pmStageOutput = [];
        foreach ($pmStages as $pmStage) {
            $pmStageOutput[] = [
                'headerText' => $pmStage->title,
                'keyField' => $pmStage->id
            ];
        }

        $pipelineData = [];
        foreach ($pmDealProgress as $dealProgress) {
            $pipelineData[] = [
                'Id' => $dealProgress->pm_deal_id,
                'Status' => $dealProgress->pm_stage_id,
                'Summary' => $dealProgress->pm_deal->title,

            ];
        }
        return [
            'stages' => $pmStageOutput,
            'pipelineData' => $pipelineData,
            'pipelineDetail' => $pmPipeline
        ];
    }
}
