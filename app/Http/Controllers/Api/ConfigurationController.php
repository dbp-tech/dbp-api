<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Repositories\ConfigurationRepository;
use Illuminate\Http\Request;

class ConfigurationController extends Controller
{
    protected $configRepo;

    public function __construct()
    {
        $this->configRepo = new ConfigurationRepository();
    }

    public function indexRole(Request $request)
    {
        $filters = $request->only(["title"]);
        return response()->json($this->configRepo->indexRole($filters, $request->header('company_id')));
    }

    public function saveRole(Request $request)
    {
        return response()->json($this->configRepo->saveRole($request->all(), $request->header('company_id')));
    }

    public function assignSystemModuleRole(Request $request)
    {
        return response()->json($this->configRepo->assignSystemModuleRole($request->all()));
    }

    public function assignUserRole(Request $request)
    {
        return response()->json($this->configRepo->assignUserRole($request->all()));
    }

    public function deleteRole(Request $request, $id = null)
    {
        return response()->json($this->configRepo->deleteRole($id, $request->header('company_id')));
    }

    public function indexModule(Request $request)
    {
        $filters = $request->only([]);
        return response()->json($this->configRepo->indexModule($filters));
    }

    public function bySubscriptionModule(Request $request)
    {
        return response()->json($this->configRepo->bySubscriptionModule($request->header('company_id')));
    }
}
