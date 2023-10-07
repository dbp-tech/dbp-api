<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Repositories\CompanyAccountRepository;
use Illuminate\Http\Request;

class CompanyAccountController extends Controller
{
    protected $companyAccountRepo;

    public function __construct()
    {
        $this->companyAccountRepo = new CompanyAccountRepository();
    }

    public function index(Request $request)
    {
        return response()->json($this->companyAccountRepo->index($request->header('company_id')));
    }

    public function save(Request $request)
    {
        return response()->json($this->companyAccountRepo->save($request->all(), $request->header('company_id')));
    }

    public function delete($id = null)
    {
        return response()->json($this->companyAccountRepo->delete($id));
    }

    public function listBank()
    {
        return response()->json(json_decode(file_get_contents(storage_path() . '/banks.json'), true));
    }
}