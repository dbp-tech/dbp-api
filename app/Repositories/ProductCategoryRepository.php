<?php

namespace App\Repositories;

use App\Models\Company;
use App\Models\ProductCategory;
use Illuminate\Support\Facades\Validator;

class ProductCategoryRepository
{
    public function index($filters, $companyId)
    {
        $productCategories = ProductCategory::with([]);
        if (!empty($filters['title'])) {
            $productCategories = $productCategories->where('title', 'LIKE', '%' . $filters['title'] . '%');
        }
        $productCategories = $productCategories->where('company_id', $companyId);
        $productCategories = $productCategories->orderBy('id', 'desc')->paginate(25);
        return $productCategories;
    }

    public function save($data, $companyId)
    {
        try {
            $validator = Validator::make($data, [
                'title' => 'required',
            ]);
            if ($validator->fails()) return resultFunction('Err code PCR-S: validation err ' . $validator->errors());

            $company = Company::find($companyId);
            if (!$company) return resultFunction('Err code PCR-S: company not found');

            $productCategory = new ProductCategory();
            $productCategory->title = $data['title'];
            $productCategory->company_id = $company->id;
            $productCategory->save();

            return resultFunction("Success to create category", true, $productCategory);
        } catch (\Exception $e) {
            return resultFunction("Err code PCR-S catch: " . $e->getMessage());
        }
    }

    public function delete($id, $companyId) {
        try {
            $productCategory =  ProductCategory::find($id);
            if (!$productCategory) return resultFunction('Err PCR-D: product category not found');

            if ($productCategory->company_id != $companyId)  return resultFunction('Err PCR-D: this category is not belongs to you');

            $productCategory->delete();

            return resultFunction("Success to delete category", true);
        } catch (\Exception $e) {
            return resultFunction("Err code PCR-D catch: " . $e->getMessage());
        }
    }
}