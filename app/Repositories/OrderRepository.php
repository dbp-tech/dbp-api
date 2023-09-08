<?php

namespace App\Repositories;

use App\Models\CheckoutForm;
use App\Models\Company;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\OrderInformation;
use App\Models\ProductCategory;
use App\Models\ProductTypeMapping;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class OrderRepository
{
    public function index($filters)
    {
        $orders = Order::with([]);
        $orders = $orders->orderBy('id', 'desc')->paginate(25);
        return $orders;
    }

    public function save($data)
    {
        try {
            DB::beginTransaction();
            $validator = Validator::make($data, [
                'cf_id' => 'required',
            ]);
            if ($validator->fails()) return resultFunction('Err OR-S: validation err ' . $validator->errors());

            $cf = CheckoutForm::with(['product.product_type_mapping_variants.variant', 'product.product_type_mapping_recipes.recipe'])->find($data['cf_id']);
            if (!$cf) return resultFunction('Err code OR-S: checkout form not found');

            $order = new Order();
            $order->invoice_number = "";
            $order->save();
            $order->invoice_number = "OO" . $order->id;
            $order->save();

            $orderInformationParams = [];
            $requestFields = json_decode($cf->requested_fields, true);
            foreach ($requestFields as $requestField) {
                $fieldSelect = collect($data['fields'])->where('key', $requestField['key'])->first();
                if ($requestField['is_required']) {
                    if (!$fieldSelect) {
                        return resultFunction("Err code OR-S: the " . $requestField['key'] . ' is required');
                    }
                    $orderInformationParams[] = [
                        "order_id" => $order->id,
                        "key_information" => $fieldSelect['key'],
                        "value_information" => $fieldSelect['value']
                    ];
                } else {
                    if ($fieldSelect) {
                        $orderInformationParams[] = [
                            "order_id" => $order->id,
                            "key_information" => $fieldSelect['key'],
                            "value_information" => $fieldSelect['value']
                        ];
                    }
                }
            }

            $productTypeMapping = ProductTypeMapping::with(['variant', 'recipe'])
                ->whereIn('id', array_column($data['orders'], 'product_type_mapping_id'))
                ->get();
            $orderDetailParams = [];
            foreach ($data['orders'] as $orderData) {
                $ptmSelect = $productTypeMapping->where('id', $orderData['product_type_mapping_id'])->first();
                if ($ptmSelect->entity_type === 'variant') {
                    $orderDetailParams[] = [
                        'order_id' => $order->id,
                        'entity_type' => $ptmSelect->entity_type,
                        'quantity' => $orderData['quantity'],
                        'title' => $ptmSelect->variant->title,
                        'image' => json_encode(json_decode($ptmSelect->variant->image)),
                        "createdAt" => date("Y-m-d H:i:s"),
                        "updatedAt" => date("Y-m-d H:i:s")
                    ];
                } elseif ($ptmSelect->entity_type === 'recipes') {
                    $orderDetailParams[] = [
                        'order_id' => $order->id,
                        'entity_type' => $ptmSelect->entity_type,
                        'quantity' => $orderData['quantity'],
                        'title' => $ptmSelect->recipe->recipe_title,
                        'image' => json_encode(json_decode("")),
                        "createdAt" => date("Y-m-d H:i:s"),
                        "updatedAt" => date("Y-m-d H:i:s")
                    ];
                }
            }

            OrderInformation::insert($orderInformationParams);
            OrderDetail::insert($orderDetailParams);

            DB::commit();
            return resultFunction("Success to create order", true);
        } catch (\Exception $e) {
            return resultFunction("Err code OR-S catch: " . $e->getMessage());
        }
    }

    public function delete($id) {
        try {
            DB::beginTransaction();
            $order =  Order::find($id);
            if (!$order) return resultFunction('Err code OR-D: order not found');
            $order->delete();

            OrderDetail::where('order_id', $id)->delete();
            OrderInformation::where('order_id', $id)->forceDelete();
            DB::commit();
            return resultFunction("Success to delete order", true);
        } catch (\Exception $e) {
            return resultFunction("Err code OR-D catch: " . $e->getMessage());
        }
    }

    public function detail($id) {
        try {
            $order =  Order::with(['order_details', 'order_informations'])->find($id);
            if (!$order) return resultFunction('Err code OR-De: order not found');
            return resultFunction("Success to delete order", true, $order);
        } catch (\Exception $e) {
            return resultFunction("Err code OR-De catch: " . $e->getMessage());
        }
    }
}