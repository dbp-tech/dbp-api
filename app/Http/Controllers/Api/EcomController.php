<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Repositories\EcomRepository;
use Illuminate\Http\Request;

class EcomController extends Controller
{
    protected $ecomRepo;

    public function __construct()
    {
        $this->ecomRepo = new EcomRepository();
    }

    public function categoryIndex(Request $request)
    {
        $filters = $request->only([]);
        return response()->json($this->ecomRepo->categoryIndex($filters, $request->header('company_id')));
    }

    public function categorySave(Request $request)
    {
        return response()->json($this->ecomRepo->categorySave($request->all(), $request->header('company_id')));
    }

    public function categoryDelete(Request $request, $id = null)
    {
        return response()->json($this->ecomRepo->categoryDelete($id, $request->header('company_id')));
    }

    public function productIndex(Request $request)
    {
        $filters = $request->only([]);
        return response()->json($this->ecomRepo->productIndex($filters, $request->header('company_id')));
    }

    public function productSave(Request $request)
    {
        return response()->json($this->ecomRepo->productSave($request->all(), $request->header('company_id')));
    }

    public function productDelete(Request $request, $id = null)
    {
        return response()->json($this->ecomRepo->productDelete($id, $request->header('company_id')));
    }

    public function getProductOnly($id, Request $request)
    {
        return response()->json($this->ecomRepo->getProductOnly($id, $request->header('company_id')));
    }

    public function deleteProductOnly($id)
    {
        return response()->json($this->ecomRepo->deleteProductOnly($id));
    }

    public function setProductOnly(Request $request)
    {
        return response()->json($this->ecomRepo->setProductOnly($request->all(), $request->header('company_id')));
    }

    public function storeIndex(Request $request)
    {
        $filters = $request->only([]);
        return response()->json($this->ecomRepo->storeIndex($filters, $request->header('company_id')));
    }

    public function storeSave(Request $request)
    {
        return response()->json($this->ecomRepo->storeSave($request->all(), $request->header('company_id')));
    }

    public function storeDelete(Request $request, $id = null)
    {
        return response()->json($this->ecomRepo->storeDelete($id, $request->header('company_id')));
    }
}