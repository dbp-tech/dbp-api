<?php

namespace App\Repositories;

use App\Models\Company;
use App\Models\PmCustomField;
use App\Models\PmCustomFieldModule;
use App\Models\PmDeal;
use App\Models\PmDealComment;
use App\Models\PmDealCustomField;
use App\Models\PmDealPipelineUser;
use App\Models\PmDealProgress;
use App\Models\PmPipeline;
use App\Models\PmPipelineUser;
use App\Models\PmStage;
use App\Models\PmType;
use App\Models\PmTypeCustomField;
use App\Models\SystemModule;
use App\Models\User;
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
                'type' => 'required'
            ]);
            if ($validator->fails()) return resultFunction('Err code PMR-S: validation err ' . $validator->errors());

            $company = Company::find($companyId);
            if (!$company) return resultFunction('Err code PMR-S: company not found');

            if ($data['id']) {
                $pmType = PmType::find($data['id']);
                if (!$pmType) return resultFunction('Err code PMR-S: type not found');

                if ($pmType->type !== $data['type']) {
                    if ($pmType->type === 'pm_pipelines') {
                        if ($pmType->pm_pipeline)  return resultFunction('Err code PMR-S: pm type is not editable, it has already use on pm_pipeline');
                    }
                    if ($pmType->type === 'pm_stages') {
                        if ($pmType->pm_stage)  return resultFunction('Err code PMR-S: pm type is not editable, it has already use on pm_stage');
                    }
                    if ($pmType->type === 'pm_deals') {
                        if ($pmType->pm_deal)  return resultFunction('Err code PMR-S: pm type is not editable, it has already use on pm_deals');
                    }
                }
            } else {
                $pmType = new PmType();
            }
            $pmType->company_id = $company->id;
            $pmType->name = $data['name'];
            $pmType->type = $data['type'];
            $pmType->save();

            return resultFunction("Success to " . ($data['id'] ? "update" : "create") . " type", true, $pmType);
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

            if ($pmType->type !== 'pm_pipelines') return resultFunction('Err code PMR-S: type of pm_types (' . $pmType->type . ') is not suitable with pm_pipelines');

            if ($data['id']) {
                $pmPipeline = PmPipeline::find($data['id']);
                if (!$pmPipeline) return resultFunction('Err code PMR-S: pipeline not found');
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

            $pmPipeline->company_id = $company->id;
            $pmPipeline->pm_type_id = $data['pm_type_id'];
            $pmPipeline->parent_id = $data['parent_id'];
            $pmPipeline->is_parent = 0;
            $pmPipeline->title = $data['title'];
            if ($data['watcher']) $pmPipeline->watcher = count($data['watcher']) ? json_encode($data['watcher']) : "[]";
            $pmPipeline->save();

            DB::commit();
            return resultFunction("Success to " . ($data['id'] ? "update" : "create") . " pm_pipelines", true, $pmPipeline);
        } catch (\Exception $e) {
            return resultFunction("Err code PMR-S catch: " . $e->getMessage());
        }
    }

    public function indexPipeline($filters, $companyId)
    {
        $pmPipeline = PmPipeline::with(['pm_type.pm_type_custom_fields.pm_custom_field', 'pm_stages', 'pm_pipeline_users.user']);
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

            $defaultWatcher = [];
            if ($pmPipeline->watcher) {
                if ($pmPipeline->watcher != '[]') {
                    $defaultWatcher = json_decode($pmPipeline->watcher, true);
                }
            }
            $pmPipeline->watcher = $defaultWatcher;

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
        $pmCF = PmCustomField::with(['pm_custom_field_modules.system_module']);
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

            if ($pmType->type !== 'pm_stages') return resultFunction('Err code PMR-S: type of pm_types (' . $pmType->type . ') is not suitable with pm_stages');

            if ($data['id']) {
                $pmStage = PmStage::find($data['id']);
                if (!$pmStage) return resultFunction('Err code PMR-S: stage not found');
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
            return resultFunction("Success to " . ($data['id'] ? "update" : "create") . " pm_stages", true, $pmStage);
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

            $pmType = PmType::find($data['pm_type_id']);
            if (!$pmType) return resultFunction('Err code PMR-S: type not found');

            if ($pmType->type !== 'pm_deals') return resultFunction('Err code PMR-S: type of pm_types (' . $pmType->type . ') is not suitable with pm_deals');

            if ($data['id']) {
                $pmDeal = PmDeal::find($data['id']);
                if (!$pmDeal) return resultFunction('Err code PMR-S: deal not found');
            } else {
                $pmDeal = new PmDeal();
            }

            $pmDeal->company_id = $company->id;
            $pmDeal->pm_type_id = $data['pm_type_id'];
            $pmDeal->title = $data['title'];
            if ($data['start_date']) $pmDeal->start_date = $data['start_date'];
            if ($data['end_date']) $pmDeal->end_date = $data['end_date'];
            if ($data['description']) $pmDeal->description = $data['description'];
            if ($data['owner']) {
                $userOwner = User::find($data['owner']);
                if (!$userOwner) return resultFunction("Err code PMR-S: user owner not found");

                $pmDeal->owner = $data['owner'];
            }
            if ($data['watcher']) $pmDeal->watcher = count($data['watcher']) ? json_encode($data['watcher']) : "[]";
            if ($data['file_upload']) $pmDeal->file_upload = count($data['file_upload']) ? json_encode($data['file_upload']) : "[]";
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
            return resultFunction("Success to " . ($data['id'] ? "update" : "create") . " pm_deals", true, $pmDeal);
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

    public function formSubmitDeal($data, $companyId)
    {
        try {
            DB::beginTransaction();

            $validator = Validator::make($data, [
                'pm_deal_id' => 'required',
                'custom_fields' => 'required',
            ]);
            if ($validator->fails()) return resultFunction('Err code PMR-S: validation err ' . $validator->errors());

            $company = Company::find($companyId);
            if (!$company) return resultFunction('Err code PMR-S: company not found');

            $pmDeal = PmDeal::find($data['pm_deal_id']);
            if (!$pmDeal) return resultFunction('Err code PMR-S: deal not found');

            PmDealCustomField::where('pm_deal_id', $pmDeal->id)->delete();

            $pmDealCF = [];
            foreach ($data['custom_fields'] as $custom_field) {
                $pmDealCF[] = [
                    'pm_deal_id' => $pmDeal->id,
                    'pm_type_id' => $custom_field['pm_type_id'],
                    'pm_custom_field_id' => $custom_field['id'],
                    'from' => $custom_field['from'],
                    'answer' => $custom_field['answer'],
                    'createdAt' => date("Y-m-d H:i:s"),
                    'updatedAt' => date("Y-m-d H:i:s")
                ];
            }

            PmDealCustomField::insert($pmDealCF);

            DB::commit();
            return resultFunction("Success to change deal", true, '');
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
            $pmDeal = PmDeal::with(['pm_type.pm_type_custom_fields.pm_custom_field',
                'pm_deal_progress.pm_pipeline.pm_type.pm_type_custom_fields.pm_custom_field',
                'pm_deal_progress.pm_stage.pm_type.pm_type_custom_fields.pm_custom_field', 'pm_deal_comments.created_by_user',
                'pm_deal_pipeline_users.user'])->find($id);
            if (!$pmDeal) return resultFunction('Err PMR-DD: deal not found');

            if ($pmDeal->company_id != $companyId) return resultFunction('Err PMR-DS: deal not found');

            $pmCustomFields = [];
            if ($pmDeal->pm_deal_progress) {
                if ($pmDeal->pm_deal_progress->pm_pipeline) {
                    if ($pmDeal->pm_deal_progress->pm_pipeline->pm_type) {
                        foreach ($pmDeal->pm_deal_progress->pm_pipeline->pm_type->pm_type_custom_fields as $custom_field) {
                            if ($custom_field->pm_custom_field) {
                                $answer = PmDealCustomField::with([])
                                    ->where('pm_deal_id', $pmDeal->id)
                                    ->where('pm_type_id', $custom_field->pm_type_id)
                                    ->where('pm_custom_field_id', $custom_field->pm_custom_field_id)
                                    ->where('from', 'pipeline')
                                    ->first();
                                $pmCustomFields = $this->setReturnCustomField($custom_field, $pmCustomFields, 'pipeline', $answer);
                            }
                        }
                    }
                }
                if ($pmDeal->pm_deal_progress->pm_stage) {
                    if ($pmDeal->pm_deal_progress->pm_stage->pm_type) {
                        foreach ($pmDeal->pm_deal_progress->pm_stage->pm_type->pm_type_custom_fields as $custom_field) {
                            if ($custom_field->pm_custom_field) {
                                $answer = PmDealCustomField::with([])
                                    ->where('pm_deal_id', $pmDeal->id)
                                    ->where('pm_type_id', $custom_field->pm_type_id)
                                    ->where('pm_custom_field_id', $custom_field->pm_custom_field_id)
                                    ->where('from', 'pipeline')
                                    ->first();
                                $pmCustomFields = $this->setReturnCustomField($custom_field, $pmCustomFields, 'stage', $answer);
                            }
                        }
                    }

                }
            }
            if ($pmDeal->pm_type) {
                foreach ($pmDeal->pm_type->pm_type_custom_fields as $custom_field) {
                    if ($custom_field->pm_custom_field) {
                        $answer = PmDealCustomField::with([])
                            ->where('pm_deal_id', $pmDeal->id)
                            ->where('pm_type_id', $custom_field->pm_type_id)
                            ->where('pm_custom_field_id', $custom_field->pm_custom_field_id)
                            ->where('from', 'pipeline')
                            ->first();
                        $pmCustomFields = $this->setReturnCustomField($custom_field, $pmCustomFields, 'deal', $answer);
                    }
                }
            }
            $pmDeal->pm_custom_fields = $pmCustomFields;
            if ($pmDeal->watcher) {
                $watchers = [];
                $pmDealWatchers = json_decode($pmDeal->watcher, true);
                foreach ($pmDealWatchers as $item) {
                    $watcherDb = User::find($item['user_id']);
                    if ($watcherDb) $watchers[] = [
                        "name" => $watcherDb->user_name,
                        "email" => $watcherDb->user_email,
                        "phone" => $watcherDb->user_phone,
                        "image" => $watcherDb->user_image,
                    ];
                }
                $pmDeal->watcher_users = $watchers;
            }

            return resultFunction("Success to get detail deal", true, $pmDeal);
        } catch (\Exception $e) {
            return resultFunction("Err code PMR-DD catch: " . $e->getMessage());
        }
    }

    public function setReturnCustomField($custom_field, $pmCustomFields, $type, $answer) {
        $pmCF = $custom_field->pm_custom_field;
        $pmCustomFields[] = [
            'id' => $pmCF->id,
            'from' => $type,
            'pm_type_id' => $custom_field->pm_type_id,
            'type' => $pmCF->type,
            'label' => $pmCF->label,
            'input_type' => $pmCF->input_type,
            'is_required' => $pmCF->is_required,
            'option_default' => $pmCF->option_default,
            'answer' => $answer
        ];
        return $pmCustomFields;
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
        $pmPipeline = PmPipeline::with(['pm_type.pm_type_custom_fields'])->find($filters['pm_pipeline_id']);

        $pmDealProgress = PmDealProgress::with(['pm_stage', 'pm_deal.pm_deal_pipeline_users.user']);
        $pmDealProgress = $pmDealProgress->where('company_id', $companyId);
        $pmDealProgress = $pmDealProgress->where('pm_pipeline_id', $filters['pm_pipeline_id']);
        $pmDealProgress = $pmDealProgress->get();

        $pmStages = PmStage::with([])
            ->where('pm_pipeline_id', $filters['pm_pipeline_id'])
            ->orderBy('pm_pipeline_id')
            ->get();

        $pmStageOutput = [];
        foreach (collect($pmStages)->sortBy("pipeline_index") as $pmStage) {
            $pmStageOutput[] = [
                'headerText' => $pmStage->title,
                'keyField' => $pmStage->id
            ];
        }

        $pipelineData = [];
        foreach ($pmDealProgress as $dealProgress) {
            $owner = null;
            if ($dealProgress->pm_deal->owner) $owner = User::find($dealProgress->pm_deal->owner);

            $watchers = [];
            if ($dealProgress->pm_deal->watcher) {
                if ($dealProgress->pm_deal->watcher) {
                    $watcherDb = json_decode($dealProgress->pm_deal->watcher, true);
                    $userIds = array_unique(array_column($watcherDb, 'user_id'));
                    if (count($userIds) > 0) {
                        $watchers = User::with([])
                            ->whereIn('id', $userIds)
                            ->get();
                    }
                }
            }

            $pmDealPipelineUsers = [];
            if ($dealProgress->pm_deal) {
                foreach ($dealProgress->pm_deal->pm_deal_pipeline_users as $user) {
                    if ($user->user) {
                        $pmDealPipelineUsers[] = $user->user;
                    }
                }
            }

            $pipelineData[] = [
                'Id' => $dealProgress->pm_deal_id,
                'Status' => $dealProgress->pm_stage_id,
                'Summary' => $dealProgress->pm_deal->title,
                'StartDate' => $dealProgress->pm_deal->start_date,
                'EndDate' => $dealProgress->pm_deal->end_date,
                'Owner' => $owner,
                'Description' => $dealProgress->pm_deal->description,
                'FileUpload' => json_decode($dealProgress->pm_deal->file_upload),
                'Watcher' => $watchers,
                'PmPipelineId' => $dealProgress->pm_pipeline_id,
                'PmStageId' => $dealProgress->pm_stage_id,
                'PmTypeId' => $dealProgress->pm_deal->pm_type_id,
                'PmDealPipelineUsers' => $pmDealPipelineUsers,
                'createdAt' => $dealProgress->createdAt,
                'updatedAt' => $dealProgress->updatedAt
            ];
        }
        return [
            'stages' => $pmStageOutput,
            'pipelineData' => $pipelineData,
            'pipelineDetail' => $pmPipeline
        ];
    }

    public function saveComment($data, $companyId)
    {
        try {
            DB::beginTransaction();

            $validator = Validator::make($data, [
                'pm_deal_id' => 'required',
                'title' => 'required',
                'created_by' => 'required'
            ]);
            if ($validator->fails()) return resultFunction('Err code PMR-S: validation err ' . $validator->errors());

            $company = Company::find($companyId);
            if (!$company) return resultFunction('Err code PMR-S: company not found');

            $pmDeal = PmDeal::find($data['pm_deal_id']);
            if (!$pmDeal) return resultFunction('Err code PMR-S: deal not found');

            $createdBy = User::find($data['created_by']);
            if (!$createdBy) return resultFunction('Err code PMR-S: user not found');

            $pmDealComment = new PmDealComment();
            $pmDealComment->pm_deal_id = $pmDeal->id;
            $pmDealComment->created_by = $createdBy->id;
            $pmDealComment->title = $data['title'];
            $pmDealComment->save();
            DB::commit();

            return resultFunction("Create pm_deal_comments successfully", true, $pmDealComment);
        } catch (\Exception $e) {
            return resultFunction("Err code PMR-S catch: " . $e->getMessage());
        }
    }

    public function assignUserPipeline($data)
    {
        try {
            DB::beginTransaction();

            $validator = Validator::make($data, [
                'pm_pipeline_id' => 'required',
                'users' => 'required',
            ]);
            if ($validator->fails()) return resultFunction('Err code PMR-AUP: validation err ' . $validator->errors());

            $pmPipeline = PmPipeline::find($data['pm_pipeline_id']);
            if (!$pmPipeline) return resultFunction('Err code PMR-AUP: pipeline not found');

            $users = User::with([])
                ->whereIn('id', $data['users'])
                ->get();

            if (count($users) !== count($data['users'])) return resultFunction("Err code PMR-AUP: user not match with request param");

            $pmPipelineUserData = [];
            foreach ($users as $user)  {
                PmPipelineUser::where('pm_pipeline_id', $pmPipeline->id)->where('user_id', $user->id)->delete();
                $pmPipelineUserData[] = [
                    'pm_pipeline_id' => $pmPipeline->id,
                    'user_id' => $user->id,
                    'createdAt' => date("Y-m-d H:i:s"),
                    'updatedAt' => date("Y-m-d H:i:s")
                ];
            }

            PmPipelineUser::insert($pmPipelineUserData);

            DB::commit();

            return resultFunction("Successfully assigning user to pm_pipelines", true);
        } catch (\Exception $e) {
            return resultFunction("Err code PMR-AUP catch: " . $e->getMessage());
        }
    }

    public function indexDpu($filters, $companyId)
    {
        $pmDpu = PmDealPipelineUser::with([]);
        $pmDpu = $pmDpu->where('company_id', $companyId);
        $pmDpu = $pmDpu->orderBy('id', 'desc')->paginate(25);
        return $pmDpu;
    }

    public function saveDpu($data, $companyId)
    {
        try {
            $validator = Validator::make($data, [
                'pm_deal_id' => 'required',
                'pm_pipeline_id' => 'required',
                'user_id' => 'required',
            ]);
            if ($validator->fails()) return resultFunction('Err code PMR-SDPU: validation err ' . $validator->errors());

            $company = Company::find($companyId);
            if (!$company) return resultFunction('Err code PMR-SDPU: company not found');

            $pmDeal = PmDeal::find($data['pm_deal_id']);
            if (!$pmDeal) return resultFunction('Err code PMR-SDPU: deal not found');

            $pmPipeline = PmPipeline::find($data['pm_pipeline_id']);
            if (!$pmPipeline) return resultFunction('Err code PMR-SDPU: pipeline not found');

            $user = User::find($data['user_id']);
            if (!$user) return resultFunction('Err code PMR-SDPU: user not found');
            
            $pmDpu = PmDealPipelineUser::with([])
                ->where('company_id', $companyId)
                ->where('pm_deal_id', $data['pm_deal_id'])
                ->where('pm_pipeline_id', $data['pm_pipeline_id'])
                ->where('user_id', $data['user_id'])
                ->first();

            if (!$pmDpu) $pmDpu = new PmDealPipelineUser();
            $pmDpu->company_id = $company->id;
            $pmDpu->pm_deal_id = $data['pm_deal_id'];
            $pmDpu->pm_pipeline_id = $data['pm_pipeline_id'];
            $pmDpu->user_id = $data['user_id'];
            $pmDpu->save();

            return resultFunction("Success to assign", true, $pmDpu);
        } catch (\Exception $e) {
            return resultFunction("Err code PMR-SDPU catch: " . $e->getMessage());
        }
    }

    public function deleteDpu($id, $companyId) {
        try {
            $pmCF =  PmDealPipelineUser::find($id);
            if (!$pmCF) return resultFunction('Err PMR-Dpu: custom field not found');

            if ($pmCF->company_id != $companyId) return resultFunction('Err PMR-Dpu: custom field not found');

            $pmCF->delete();

            return resultFunction("Success to delete assign", true);
        } catch (\Exception $e) {
            return resultFunction("Err code PMR-Dpu catch: " . $e->getMessage());
        }
    }

    public function indexCFM($filters, $companyId)
    {
        $pmCFM = PmCustomFieldModule::with(['pm_custom_field', 'system_module']);
        $pmCFM = $pmCFM->where('company_id', $companyId);
        $pmCFM = $pmCFM->orderBy('id', 'desc')->get();
        return $pmCFM;
    }

    public function saveCFM($data, $companyId)
    {
        try {
            DB::beginTransaction();
            $validator = Validator::make($data, [
                'pm_custom_field_id' => 'required',
                'modules' => 'required',
            ]);
            if ($validator->fails()) return resultFunction('Err code PMR-SCFM: validation err ' . $validator->errors());

            $company = Company::find($companyId);
            if (!$company) return resultFunction('Err code PMR-SCFM: company not found');

            $pmCustomField = PmCustomField::find($data['pm_custom_field_id']);
            if (!$pmCustomField) return resultFunction('Err code PMR-SCFM: custom field not found');

            $systemModuleIds = array_column($data['modules'], 'system_module_id');
            $systemModules = SystemModule::with([])
                ->whereIn('id', $systemModuleIds)
                ->get();
            if (count($systemModuleIds) !== count($systemModules)) return resultFunction("Err code PMR-SCFM: the module is not match with param input");

            $paramSave = [];
            foreach ($data['modules'] as $item) {
                $paramSave[] = [
                    'company_id' => $company->id,
                    'pm_custom_field_id' => $pmCustomField->id,
                    'system_module_id' => $item['system_module_id'],
                    'pm_details' => json_encode($item['details']),
                    'createdAt' => date("Y-m-d H:i:s"),
                    'updatedAt' => date("Y-m-d H:i:s")
                ];
            }

            PmCustomFieldModule::where('pm_custom_field_id', $pmCustomField->id)->delete();

            PmCustomFieldModule::insert($paramSave);

            DB::commit();
            return resultFunction("Success to create custom field module", true);
        } catch (\Exception $e) {
            return resultFunction("Err code PMR-SCFM catch: " . $e->getMessage());
        }
    }
}
