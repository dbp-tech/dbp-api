<?php

namespace App\Repositories;

use App\Models\CheckoutForm;
use App\Models\CheckoutFormBumpProduct;
use App\Models\Product;
use App\Models\ProductCategory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class CheckoutFormRepository
{
    public function index($filters)
    {
        $cf = CheckoutForm::with(['product.product_type_mapping_variants.variant', 'product.product_type_mapping_recipes.recipe',
            'checkout_form_bump_products.product_type_mapping_variants.variant']);
        $cf = $cf->orderBy('id', 'desc')->paginate(25);
        return $cf;
    }

    public function save($data)
    {
        try {
            DB::beginTransaction();
            $validator = Validator::make($data, [
                'product_id' => 'required',
            ]);
            if ($validator->fails()) return resultFunction('Err code CFR-S: validation err ' . $validator->errors());

            $product = Product::find($data['product_id']);
            if (!$product) return resultFunction('Err code CFR-S: product not found');

            $cf = new CheckoutForm();
            $cf->product_id = $data['product_id'];
            $cf->title = $data['title'];
            $cf->template = json_encode($data['template']);
            $cf->show_product_image = true;
            $cf->guarantee_seals = json_encode($data['guarantee_seals']);
            $cf->requested_fields = json_encode($data['requested_fields']);
            $cf->is_dropship = $data['is_dropship'];
            $cf->buy_button = json_encode($data['buy_button']);
            $cf->video = $data['video'];
            $cf->content = $data['content'];
            $cf->coupon_field = $data['coupon_field'];
            $cf->bullet_points = json_encode($data['bullet_points']);
            $cf->template = json_encode($data['template']);
            $cf->is_order_summary = $data['is_order_summary'];
            $cf->testimonials = json_encode($data['testimonials']);
            $cf->tracking = json_encode($data['tracking']);
            $cf->headline_text = json_encode($data['headline_text']);
            $cf->success_video = json_encode($data['success_video']);
            $cf->bank_accounts = json_encode($data['bank_accounts']);
            $cf->save();

            if (count($data['bump_products']) > 0) {
                $productBp = Product::with([])
                    ->whereIn("id", $data['bump_products'])
                    ->get();
                if (count($productBp) !== count($data['bump_products'])) return resultFunction("Err code CFR-S: bump product is not match");
            }

            if ($data['bump_products']) {
                CheckoutFormBumpProduct::insert([
                    'checkout_form_id' => $cf->id,
                    'product_id' => $data['bump_products'],
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

    public function detailEmbedForm($id) {
        try {
            $cf =  CheckoutForm::with(['product.product_type_mapping_variants.variant', 'product.product_type_mapping_recipes.recipe',
                'checkout_form_bump_products.product.product_type_mapping_variants.variant'
                , 'checkout_form_bump_products.product.product_type_mapping_recipes.recipe'
            ])
                ->find($id);
            if (!$cf) return resultFunction('Err CFR-De: checkout form not found');

            $bumpTitle = "";
            $bumpDesc = "";
            if ($cf->checkout_form_bump_products) {
                $bumpTitle = $cf->checkout_form_bump_products->product->title;
                $bumpDesc = $cf->checkout_form_bump_products->product->description;
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
                    'bumpDescription' => $bumpDesc
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