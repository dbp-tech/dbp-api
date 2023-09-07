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
        $cf = CheckoutForm::with([]);
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
            if ($validator->fails()) return resultFunction('Err CFR-S: validation err ' . $validator->errors());

            $product = Product::find($data['product_id']);
            if (!$product) return resultFunction('Err CFR-S: product not found');

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

            $cFBump = [];
            foreach ($data['bump_products'] as $item) {
                $cFBump[] = [
                    'checkout_form_id' => $cf->id,
                    'product_id' => $item,
                    "createdAt" => date("Y-m-d H:i:s"),
                    "updatedAt" => date("Y-m-d H:i:s")
                ];
            }

            if (count($cFBump) > 0) {
                CheckoutFormBumpProduct::insert($cFBump);
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
            if (!$cf) return resultFunction('Err CFR-D: product category not found');
            $cf->delete();

            CheckoutFormBumpProduct::where('checkout_form_id', $id)->delete();

            DB::commit();
            return resultFunction("Success to delete checkout form", true);
        } catch (\Exception $e) {
            return resultFunction("Err code CFR-D catch: " . $e->getMessage());
        }
    }
}