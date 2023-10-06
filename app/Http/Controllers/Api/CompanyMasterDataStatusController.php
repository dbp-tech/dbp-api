<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Repositories\CompanyMasterDataStatusRepository;
use Illuminate\Http\Request;

class CompanyMasterDataStatusController extends Controller
{
    protected $companyMasterDataStatus;

    public function __construct()
    {
        $this->companyMasterDataStatus = new CompanyMasterDataStatusRepository();
    }

    public function index(Request $request)
    {
        return response()->json($this->companyMasterDataStatus->index($request->header('company_id')));
    }

    public function save(Request $request)
    {
        return response()->json($this->companyMasterDataStatus->save($request->all(), $request->header('company_id')));
    }

    public function delete($id = null)
    {
        return response()->json($this->companyMasterDataStatus->delete($id));
    }
}