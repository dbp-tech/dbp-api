<?php

namespace App\Repositories;

use App\Models\Company;
use App\Models\Variant;
use Illuminate\Support\Facades\Validator;

class VariantRepository
{
    public function index($filters)
    {
        $variant = Variant::with([]);
        if (!empty($filters['title'])) {
            $variant = $variant->where('title', 'LIKE', '%' . $filters['title'] . '%');
        }
        if (!empty($filters['company_id'])) {
            $variant = $variant->where('company_id', $filters['company_id']);
        }
        $variant = $variant->orderBy('id', 'desc')->paginate(25);
        return $variant;
    }

    public function save($data)
    {
        try {
            $validator = Validator::make($data, [
                'company_id' => 'required',
                'title' => 'required',
                'price' => 'required',
                'image' => 'required',
            ]);
            if ($validator->fails()) return resultFunction('Err code VR-S: validation err ' . $validator->errors());

            $company = Company::find($data['company_id']);
            if (!$company) return resultFunction('Err code VR-S: company not found');

            $variant = new Variant();
            $variant->company_id = $data['company_id'];
            $variant->title = $data['title'];
            $variant->price = $data['price'];
            $variant->image = json_encode($data['image']);
            $variant->save();

            return resultFunction("Success to create category", true, $variant);
        } catch (\Exception $e) {
            return resultFunction("Err code VR-S catch: " . $e->getMessage());
        }
    }

    public function delete($id) {
        try {
            $variant =  Variant::find($id);
            if (!$variant) return resultFunction('Err VR-D: product category not found');
            $variant->delete();

            return resultFunction("Success to delete variant", true);
        } catch (\Exception $e) {
            return resultFunction("Err code VR-D catch: " . $e->getMessage());
        }
    }
}