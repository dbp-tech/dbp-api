<?php

namespace App\Repositories;

use App\Models\Company;
use App\Models\CompanyAccount;
use Illuminate\Support\Facades\Validator;

class CompanyAccountRepository
{
    public function index($companyId)
    {
        $companyAccount = CompanyAccount::with([]);
        $companyAccount = $companyAccount->where('company_id', $companyId);
        $companyAccount = $companyAccount->orderBy('id', 'desc')->paginate(25);
        return $companyAccount;
    }

    public function save($data, $companyId)
    {
        try {
            $validator = Validator::make($data, [
                'account_number' => 'required',
                'account_name' => 'required',
                'account_bank' => 'required',
            ]);
            if ($validator->fails()) return resultFunction('Err code CMDS-S: validation err ' . $validator->errors());

            $company = Company::find($companyId);
            if (!$company) return resultFunction('Err code CMDS-S: company not found');

            $companyAccount = new CompanyAccount();
            $companyAccount->company_id = $company->id;
            $companyAccount->account_number = $data['account_number'];
            $companyAccount->account_name = $data['account_name'];
            $companyAccount->account_bank = $data['account_bank'];
            $companyAccount->save();

            return resultFunction("Success to create company account data status", true, $companyAccount);
        } catch (\Exception $e) {
            return resultFunction("Err code CMDS-S catch: " . $e->getMessage());
        }
    }

    public function delete($id) {
        try {
            $companyAccount =  CompanyAccount::find($id);
            if (!$companyAccount) return resultFunction('Err CMDS-D: company account not found');
            $companyAccount->delete();

            return resultFunction("Success to delete  company account ", true);
        } catch (\Exception $e) {
            return resultFunction("Err code CMDS-D catch: " . $e->getMessage());
        }
    }
}