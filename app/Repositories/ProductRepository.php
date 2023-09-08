<?php

namespace App\Repositories;

use App\Models\CheckoutForm;
use App\Models\Company;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductEntityType;
use App\Models\ProductTypeMapping;
use App\Models\Variant;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ProductRepository
{
    public function index($filters)
    {
        $product = Product::with(['product_type_mapping_variants.variant', 'product_type_mapping_recipes.recipe']);
        if (!empty($filters['company_id'])) {
            $product = $product->where('company_id', $filters['company_id']);
        }
        $product = $product->orderBy('id', 'desc')->paginate(25);
        return $product;
    }

    public function save($data)
    {
        try {
            DB::beginTransaction();
            $validator = Validator::make($data, [
                'company_id' => 'required',
                'title' => 'required',
                'price' => 'required',
                'image' => 'required',
            ]);
            if ($validator->fails()) return resultFunction('Err code PR-S: validation err ' . $validator->errors());

            $company = Company::find($data['company_id']);
            if (!$company) return resultFunction('Err code PR-S: company not found');

            $productCategory = ProductCategory::find($data['category_id']);
            if (!$productCategory) return resultFunction('Err code PR-S: category not found');

            $product = new Product();
            $product->company_id = $data['company_id'];
            $product->category_id = $data['category_id'];
            $product->title = $data['title'];
            $product->description = $data['description'];
            $product->price = $data['price'];
            $product->image = json_encode($data['image']);
            $product->save();

            $ptmParams = [];
            foreach ($data['details'] as $detail) {
                $productEntityType = ProductEntityType::find($detail['entity_id']);
                if (!$productEntityType) return resultFunction("Err code PR-S: product entity type not found");

                if ($productEntityType->product_type_name === 'variant') {
                    $dataOri = Variant::with([])
                        ->whereIn('id', $detail['entity_data'])
                        ->get();
                } else {
                    $dataOri = Variant::with([])
                        ->whereIn('id', $detail['entity_data'])
                        ->get();
                }
                if (count($dataOri) !== count($detail['entity_data'])) return resultFunction("Err code PR-S: the data is not match");
                foreach ($detail['entity_data'] as $item) {
                    $ptmParams[] = [
                        "product_id" => $product->id,
                        "entity_type" => $productEntityType->product_type_name,
                        "entity_id" => $item,
                        "createdAt" => date("Y-m-d H:i:s"),
                        "updatedAt" => date("Y-m-d H:i:s")
                    ];
                }
            }
            ProductTypeMapping::insert($ptmParams);
            CheckoutForm::insert([
                "product_id" => $product->id,
                "title" => "default checkout form",
                "template" => '{
                    "type": "right sidebar",
                    "background_color": "blue"
                }',
                "header" => '{
                    "image": {
                        "url": "",
                        "path": ""
                    },
                    "title": {
                        "is_show": true,
                        "message": ""
                    },
                    "tagline": {
                        "is_show": true,
                        "message": ""
                    }
                }',
                "show_product_image" => true,
                "guarantee_seals" => '{
                    "is_show": true,
                    "message": "100% jaminan kepuasan"
                }',
                "requested_fields" => '[
                    {
                        "key": "name",
                        "field": "input",
                        "type": "text",
                        "is_required": true,
                        "label": "custom field",
                        "placeholder": ""
                    },
                    {
                        "key": "phone",
                        "field": "input",
                        "type": "text",
                        "is_required": true,
                        "label": "custom field",
                        "placeholder": ""
                    },
                    {
                        "key": "address",
                        "field": "textarea",
                        "is_required": false,
                        "label": "Your full address",
                        "placeholder": ""
                    },
                    {
                        "key": "sex",
                        "field": "select",
                        "is_required": false,
                        "label": "custom field",
                        "placeholder": "",
                        "options": [
                            {
                                "label": "male",
                                "value": "female"
                            }
                        ]
                    }
                ]',
                "is_dropship" => false,
                "buy_button" => '{
                    "label": "Gabung Sekarang",
                    "color": "red"
                }',
                "video" => "",
                "content" => "ini content",
                "coupon_field" => true,
                "bullet_points" => '[
                    "point1",
                    "point2"
                ]',
                "is_order_summary" => false,
                "testimonials" => '[
                    {
                        "image": {"url": "", "path": ""},
                        "name": "",
                        "description": ""
                    }
                ]',
                "tracking" => '[
                    {
                        "type": "facebook-pixel",
                        "id": 1231231,
                        "data": ["ViewContent", "AddToCart"]
                    },
                    {
                        "type": "google-tag-manager",
                        "data": ["GTM-123", "GTM-124"]
                    }
                ]',
                "headline_text" => '{
                    "is_show_title": false,
                    "title": "",
                    "headeline": "Terimakasih sudah melakukan order {{product_name}}"
                }',
                "success_video" => "",
                "bank_accounts" => '[
                    {
                        "bank": "BCA",
                        "account_number": 123123,
                        "account_holder_name": "Budi"
                    }
                ]',
                "createdAt" => date("Y-m-d H:i:s"),
                "updatedAt" => date("Y-m-d H:i:s")
            ]);

            DB::commit();
            return resultFunction("Success to create product", true);
        } catch (\Exception $e) {
            DB::rollBack();
            return resultFunction("Err code PR-S catch: " . $e->getMessage());
        }
    }

    public function delete($id) {
        try {
            DB::beginTransaction();
            $product =  Product::find($id);
            if (!$product) return resultFunction('Err PR-D: product category not found');
            $product->delete();

            ProductTypeMapping::where('product_id', $id)->delete();
            CheckoutForm::where('product_id', $id)->delete();

            DB::commit();
            return resultFunction("Success to delete product", true);
        } catch (\Exception $e) {
            return resultFunction("Err code PR-D catch: " . $e->getMessage());
        }
    }
}