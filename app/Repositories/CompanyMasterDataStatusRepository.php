<?php

namespace App\Repositories;

use App\Models\Company;
use App\Models\CompanyMasterDataStatus;
use App\Models\Variant;
use Illuminate\Support\Facades\Validator;

class CompanyMasterDataStatusRepository
{
    public function index($companyId)
    {
        $companyMDStatus = CompanyMasterDataStatus::with([]);
        $companyMDStatus = $companyMDStatus->where('company_id', $companyId);
        $companyMDStatus = $companyMDStatus->orderBy('id', 'desc')->paginate(25);
        return $companyMDStatus;
    }

    public function save($data, $companyId)
    {
        try {
            $validator = Validator::make($data, [
                'title' => 'required',
                'index' => 'required',
            ]);
            if ($validator->fails()) return resultFunction('Err code CMDS-S: validation err ' . $validator->errors());

            $company = Company::find($companyId);
            if (!$company) return resultFunction('Err code CMDS-S: company not found');

            $companyMDS = new CompanyMasterDataStatus();
            $companyMDS->company_id = $company->id;
            $companyMDS->title = $data['title'];
            $companyMDS->index = $data['index'];
            $companyMDS->save();

            return resultFunction("Success to create company master data status", true, $companyMDS);
        } catch (\Exception $e) {
            return resultFunction("Err code CMDS-S catch: " . $e->getMessage());
        }
    }

    public function delete($id) {
        try {
            $companyMDS =  CompanyMasterDataStatus::find($id);
            if (!$companyMDS) return resultFunction('Err CMDS-D: product category not found');
            $companyMDS->delete();

            return resultFunction("Success to delete company master data status ", true);
        } catch (\Exception $e) {
            return resultFunction("Err code CMDS-D catch: " . $e->getMessage());
        }
    }
}