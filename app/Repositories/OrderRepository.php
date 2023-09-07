<?php

namespace App\Repositories;

use App\Models\CheckoutForm;
use App\Models\Company;
use App\Models\ProductCategory;
use Illuminate\Support\Facades\Validator;

class OrderRepository
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
                'cf_id' => 'required',
            ]);
            if ($validator->fails()) return resultFunction('Err OR-S: validation err ' . $validator->errors());

            $cf = CheckoutForm::find($data['df_id']);
            if (!$cf) return resultFunction('Err OR-S: checkout form not found');

            return resultFunction("Success to create order", true);
        } catch (\Exception $e) {
            return resultFunction("Err code OR-S catch: " . $e->getMessage());
        }
    }

    public function delete($id) {
        try {
            $productCategory =  ProductCategory::find($id);
            if (!$productCategory) return resultFunction('Err OR-D: product category not found');
            $productCategory->delete();

            return resultFunction("Success to delete category", true);
        } catch (\Exception $e) {
            return resultFunction("Err code OR-D catch: " . $e->getMessage());
        }
    }
}