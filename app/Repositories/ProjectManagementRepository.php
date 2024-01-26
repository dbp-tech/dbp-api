<?php

namespace App\Repositories;

use App\Models\Company;
use App\Models\PmCustomField;
use App\Models\PmPipeline;
use App\Models\PmType;
use Illuminate\Support\Facades\Validator;

class ProjectManagementRepository
{
    public function indexType($filters, $companyId)
    {
        $pmType = PmType::with([]);
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

    public function savePipeline($data, $companyId)
    {
        try {
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

            $pmPipeline->company_id = $company->id;
            $pmPipeline->pm_type_id = $data['pm_type_id'];
            $pmPipeline->parent_id = $data['parent_id'];
            $pmPipeline->is_parent = 0;
            $pmPipeline->title = $data['title'];
            $pmPipeline->save();

            return resultFunction("Success to create pipeline", true, $pmPipeline);
        } catch (\Exception $e) {
            return resultFunction("Err code PMR-S catch: " . $e->getMessage());
        }
    }

    public function indexPipeline($filters, $companyId)
    {
        $pmPipeline = PmPipeline::with([]);
        if (in_array($filters['is_parent'], [0, 1])) {
            $pmPipeline = $pmPipeline->where('is_parent', $filters['is_parent']);
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
}