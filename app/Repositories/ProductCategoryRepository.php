<?php

namespace App\Repositories;

use App\Models\Company;
use App\Models\ProductCategory;
use Illuminate\Support\Facades\Validator;

class ProductCategoryRepository
{
    public function index($filters)
    {
        $productCategories = ProductCategory::with([]);
        if (!empty($filters['title'])) {
            $productCategories = $productCategories->where('title', 'LIKE', '%' . $filters['title'] . '%');
        }
        if (!empty($filters['company_id'])) {
            $productCategories = $productCategories->where('company_id', $filters['company_id']);
        }
        $productCategories = $productCategories->orderBy('id', 'desc')->paginate(25);
        return $productCategories;
    }

    public function save($data)
    {
        try {
            $validator = Validator::make($data, [
                'title' => 'required',
                'company_id' => 'required',
            ]);
            if ($validator->fails()) return resultFunction('Err PCR-S: validation err ' . $validator->errors());

            $company = Company::find($data['company_id']);
            if (!$company) return resultFunction('Err PCR-S: company not found');

            $productCategory = new ProductCategory();
            $productCategory->title = $data['title'];
            $productCategory->company_id = $data['company_id'];
            $productCategory->save();

            return resultFunction("Success to create category", true, $productCategory);
        } catch (\Exception $e) {
            return resultFunction("Err code PCR-S catch: " . $e->getMessage());
        }
    }
}