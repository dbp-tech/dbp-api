<?php

namespace App\Repositories;

use App\Models\CheckoutForm;
use App\Models\CheckoutFormBumpProduct;
use App\Models\EcomProduct;
use App\Models\Product;
use App\Models\ProductCategory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class CheckoutFormRepository
{
    public function index($filters)
    {
        $cf = CheckoutForm::with([
            'product.product_type_mapping_variants.variant', 'product.product_type_mapping_recipes.recipe',
            'checkout_form_bump_products.product.product_type_mapping_variants.variant'
        ]);
        $cf = $cf->orderBy('id', 'desc')->paginate(25);
        return $cf;
    }

    public function save($data)
    {
        try {
            DB::beginTransaction();
            $validator = Validator::make($data, [
                'product_id' => 'required',
                'doc_id' => 'required',
            ]);
            if ($validator->fails()) return resultFunction('Err code CFR-S: validation err ' . $validator->errors());

            $product = EcomProduct::find($data['product_id']);
            if (!$product) return resultFunction('Err code CFR-S: product not found');

            $cf = new CheckoutForm();
            $cf->doc_id = $data['doc_id'];
            $cf->product_id = $data['product_id'];
            $cf->title = $data['title'];
            $cf->save();

            if (isset($data['bump_products'])) {
                $productBp = EcomProduct::with([])
                    ->where("id", $data['bump_products']['product_id'])
                    ->first();
                if (!$productBp) return resultFunction("Err code CFR-S: bump product not found");

                CheckoutFormBumpProduct::insert([
                    'checkout_form_id' => $cf->id,
                    'product_id' => $data['bump_products']['product_id'],
                    'headline_title' => $data['bump_products']['headline_title'],
                    'use_address' => $data['bump_products']['use_address'],
                    "createdAt" => date("Y-m-d H:i:s"),
                    "updatedAt" => date("Y-m-d H:i:s")
                ]);
            }

            DB::commit();
            return resultFunction("Success to create checkout form", true, $cf);
        } catch (\Exception $e) {
            return resultFunction("Err code CFR-S catch: " . $e->getMessage());
        }
    }

    public function delete($id) {
        try {
            DB::beginTransaction();
            $cf =  CheckoutForm::find($id);
            if (!$cf) return resultFunction('Err CFR-D: checkout form not found');
            $cf->delete();

            CheckoutFormBumpProduct::where('checkout_form_id', $id)->delete();

            DB::commit();
            return resultFunction("Success to delete checkout form", true);
        } catch (\Exception $e) {
            return resultFunction("Err code CFR-D catch: " . $e->getMessage());
        }
    }

    public function detail($id) {
        try {
            $cf =  CheckoutForm::with(['product.product_type_mapping_variants.variant', 'product.product_type_mapping_recipes.recipe',
                'checkout_form_bump_products.product'])
                ->find($id);
            if (!$cf) return resultFunction('Err CFR-De: checkout form not found');

            return resultFunction("", true, $cf);
        } catch (\Exception $e) {
            return resultFunction("Err code CFR-De catch: " . $e->getMessage());
        }
    }

    public function detailEmbedForm($id, $data) {
        try {
            $cf =  CheckoutForm::with(['product.product_type_mapping_variants.variant', 'product.product_type_mapping_recipes.recipe',
                'checkout_form_bump_products.product.product_type_mapping_variants.variant'
                , 'checkout_form_bump_products.product.product_type_mapping_recipes.recipe', 'product.company'
            ])
                ->find($id);
            if (!$cf) return resultFunction('Err CFR-De: checkout form not found');

            if ($cf->product_id != $data['product_id']) return resultFunction('Err CFR-De: product not found');

            if ($cf->product->company->company_doc_id !== $data['company_doc_id']) return resultFunction('Err CFR-De: Company not match');

            $bumpTitle = "";
            $bumpDesc = "";
            $useAddress = [];
            if ($cf->checkout_form_bump_products) {
                $bumpTitle = $cf->checkout_form_bump_products->product->title;
                $bumpDesc = $cf->checkout_form_bump_products->product->description;
                if ($cf->checkout_form_bump_products->use_address) {
                    $useAddress = [
                        [
                            'key' => 'district',
                            'field' => 'select',
                            'type' => 'select',
                            'is_required' => false,
                            'label' => 'Kecamatan Pemesan',
                            'placeholder' => 'Kecamatan Pemesan',
                            'isChecked' => false,
                        ],
                        [
                            'key' => 'address',
                            'field' => 'input',
                            'type' => 'textarea',
                            'is_required' => false,
                            'label' => 'Alamat Lengkap Pemesan',
                            'placeholder' => 'Alamat Lengkap Pemesan',
                            'isChecked' => false
                        ]
                    ];
                }
            }

            $buttonData = json_decode($cf->buy_button, true);

            $fbPixel = "";
            $trackingData = collect(json_decode($cf->tracking, true));
            $fbData = $trackingData->where('type', 'facebook-pixel')->first();
            if ($fbData) {
                $fbPixel = [
                    "enabled" => true,
                    'pixelId' => $fbData['id'],
                    'event' => $fbData['data']
                ];
            }

            $gtm = "";
            $gtmData = $trackingData->where('type', 'google-tag-manager')->first();
            if ($gtmData) {
                $gtm = $gtmData['data'];
            }

            return resultFunction("", true, [
                'inputSection' => [
                    'requested_field' => json_decode($cf->requested_fields, true)
                ],
                'paymentSection' => [
                    "paymentOptions" => json_decode($cf->bank_accounts, true)
                ],
                'bumpSection' => [
                    'bumpTitle' => $bumpTitle,
                    'bumpDescription' => $bumpDesc,
                    "use_address" => $useAddress
                ],
                "summarySection" => [
                    "productData" => $cf->product,
                    "bumpData" => $cf->checkout_form_bump_products->product,
                    'uniqueCode' => $cf->product->unique_code,
                    'price' => $cf->product->price,
                    'salePrice' => $cf->product->sale_price
                ],
                "buttonSection" => [
                    'buttonTitle' => $buttonData['label'],
                    "buttonColor" => $buttonData['color']
                ],
                "tracker" => [
                    'fb-pixel' => $fbPixel,
                    'gtm' => $gtm
                ]
            ]);
        } catch (\Exception $e) {
            return resultFunction("Err code CFR-De catch: " . $e->getMessage());
        }
    }
}