<?php

namespace App\Repositories;

use App\Models\Company;
use App\Models\Role;
use App\Models\RoleSystemModule;
use App\Models\SystemModule;
use App\Models\UserRole;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ConfigurationRepository
{
    public function indexRole($filters, $companyId)
    {
        $pmType = Role::with(['role_system_modules.system_module']);
        if (!empty($filters['title'])) {
            $pmType = $pmType->where('title', 'LIKE', '%' . $filters['title'] . '%');
        }
        $pmType = $pmType->where('company_id', $companyId);
        $pmType = $pmType->orderBy('id', 'desc')->paginate(25);
        return $pmType;
    }

    public function saveRole($data, $companyId)
    {
        try {
            $validator = Validator::make($data, [
                'title' => 'required'
            ]);
            if ($validator->fails()) return resultFunction('Err code CR-S: validation err ' . $validator->errors());

            $company = Company::find($companyId);
            if (!$company) return resultFunction('Err code CR-S: company not found');

            if ($data['id']) {
                $role = Role::find($data['id']);
                if (!$role) return resultFunction('Err code CR-S: type not found');
            } else {
                $role = new Role();
            }
            $role->company_id = $company->id;
            $role->title = $data['title'];
            $role->save();

            return resultFunction("Success to " . ($data['id'] ? "update" : "create") . " role", true, $role);
        } catch (\Exception $e) {
            return resultFunction("Err code CR-S catch: " . $e->getMessage());
        }
    }

    public function deleteRole($id, $companyId) {
        try {
            $pmType =  Role::find($id);
            if (!$pmType) return resultFunction('Err CR-D:role not found');

            if ($pmType->company_id != $companyId) return resultFunction('Err CR-D: role not found');
            $pmType->delete();

            return resultFunction("Success to delete role", true);
        } catch (\Exception $e) {
            return resultFunction("Err code CR-D catch: " . $e->getMessage());
        }
    }

    public function indexModule($filters)
    {
        $systemModules = SystemModule::with([]);
        $systemModules = $systemModules->orderBy('id', 'desc')->get();
        return $systemModules;
    }

    public function bySubscriptionModule($companyId)
    {
        try {
            $company = Company::with(['organization.organization_subscription_log_mapping.subscription_log.subscription.subscription_module_mappings.system_module'])
                ->find($companyId);
            if (!$company) return resultFunction('Err code CR-SBM: company not found');
            if (!$company->organization) return resultFunction('Err code CR-SBM: organization not found');
            if (!$company->organization->organization_subscription_log_mapping) return resultFunction('Err code CR-SBM: organization mapping not found');

            $mapping = $company->organization->organization_subscription_log_mapping;
            if (!$mapping->subscription_log) return resultFunction('Err code CR-SBM: subscription log not found');
            if (!$mapping->subscription_log->subscription) return resultFunction('Err code CR-SBM: subscription not found');

            $subMappings = $mapping->subscription_log->subscription->subscription_module_mappings;
            $modules = [];
            foreach ($subMappings as $subMapping) {
                if ($subMapping->system_module) {
                    $modules[] = $subMapping->system_module;
                }
            }

            return resultFunction("", true, $modules);
        } catch (\Exception $e) {
            return resultFunction("Err code CR-SBM catch: " . $e->getMessage());
        }
    }

    public function assignSystemModuleRole($data)
    {
        try {
            DB::beginTransaction();
            $validator = Validator::make($data, [
                'role_id' => 'required',
                'system_modules' => 'required'
            ]);
            if ($validator->fails()) return resultFunction('Err code CR-ASMR: validation err ' . $validator->errors());

            $role = Role::find($data['role_id']);
            if (!$role) return resultFunction('Err code CR-ASMR: role not found');

            $systemModules = SystemModule::with([])
                ->whereIn('id', $data['system_modules'])
                ->get();
            if (count($systemModules) !== count($data['system_modules'])) return resultFunction('Err code CR-ASMR: module not found');

            $roleSystemModuleParams = [];
            foreach ($systemModules as $module) {
                $roleSystemModuleParams[] = [
                    'role_id' => $role->id,
                    'system_module_id' => $module->id,
                    'createdAt' => date('Y-m-d H:i:s'),
                    'updatedAt' => date('Y-m-d H:i:s')
                ];
            }

            RoleSystemModule::where('role_id', $role->id)->delete();
            RoleSystemModule::insert($roleSystemModuleParams);

            DB::commit();
            return resultFunction("Success to assign role", true, $role);
        } catch (\Exception $e) {
            return resultFunction("Err code CR-ASMR catch: " . $e->getMessage());
        }
    }

    public function assignUserRole($data)
    {
        try {
            DB::beginTransaction();
            $validator = Validator::make($data, [
                'user_id' => 'required',
                'role_id' => 'required'
            ]);
            if ($validator->fails()) return resultFunction('Err code CR-AUR: validation err ' . $validator->errors());

            $userId = Role::find($data['user_id']);
            if (!$userId) return resultFunction('Err code CR-AUR: user not found');

            $role = Role::find($data['role_id']);
            if (!$role) return resultFunction('Err code CR-AUR: role not found');

            UserRole::insert([
                'role_id' => $role->id,
                'user_id' => $userId->id,
                'createdAt' => date('Y-m-d H:i:s'),
                'updatedAt' => date('Y-m-d H:i:s')
            ]);

            DB::commit();
            return resultFunction("Success to assign user role", true, $role);
        } catch (\Exception $e) {
            return resultFunction("Err code CR-AUR catch: " . $e->getMessage());
        }
    }
}
