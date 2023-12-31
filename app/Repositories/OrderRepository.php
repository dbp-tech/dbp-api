<?php

namespace App\Repositories;

use App\Models\CheckoutForm;
use App\Models\Company;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\OrderFuHistory;
use App\Models\OrderInformation;
use App\Models\OrderStatus;
use App\Models\ProductTypeMapping;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class OrderRepository
{
    public function index($filters, $companyId)
    {
        $orders = Order::with(["order_details", "order_informations", "order_fu_histories", "order_statuses", "checkout_form.product.product_fu_templates"]);
        $orders = $orders
            ->where('company_id', $companyId)
            ->orderBy('id', 'desc')->paginate(25);
        return $orders;
    }

    public function save($data, $companyId)
    {
        try {
            DB::beginTransaction();
            $validator = Validator::make($data, [
                'cf_id' => 'required',
            ]);
            if ($validator->fails()) return resultFunction('Err OR-S: validation err ' . $validator->errors());

            $company = Company::with([])->where("company_doc_id", $companyId)->first();
            if (!$company)return resultFunction("Err OR-S: company not found");

            $cf = CheckoutForm::with([
                'product.product_type_mapping_variants.variant',
                'product.product_type_mapping_recipes.recipe',
                'checkout_form_bump_products.product.product_type_mapping_variants.variant',
                'checkout_form_bump_products.product.product_type_mapping_recipes.recipe'
            ])->find($data['cf_id']);
            if (!$cf) return resultFunction('Err code OR-S: checkout form not found');

            if ($data['is_bump_order']) {
                if (!$cf->checkout_form_bump_products)  return resultFunction("Err code OR-S: the bump product not found");
            }

            $order = new Order();
            $order->company_id = $company->id;
            $order->checkout_form_id = $cf->id;
            $order->invoice_number = "";
            $order->last_status = 'pending';
            $order->save();
            $order->invoice_number = "OO" . $order->id;
            $order->is_bump_product = $data['is_bump_order'] ? 1 : 0;
            $order->delivery_local_label = $data['delivery_local']['label'];
            $order->delivery_local_fee = $data['delivery_local']['fee'];
            $order->product_total = $cf->product->sale_price;
            $order->bump_product_total = 0;
            if ($data['is_bump_order']) {
                $order->bump_product_total = $cf->checkout_form_bump_products->product->sale_price;
            }
            $order->total_price = $order->product_total + $order->bump_product_total + $order->delivery_local_fee;
            $order->save();

            $orderInformationParams = [];
            $requestFields = json_decode($cf->requested_fields, true);
            $customerParams = [
                'company_id' => $company->id,
                'uuid' => null,
                'name' => '',
                'email' => '',
                'phone' => '',
                "createdAt" => date("Y-m-d H:i:s"),
                "updatedAt" => date("Y-m-d H:i:s")
            ];
            foreach ($requestFields as $requestField) {
                $fieldSelect = collect($data['fields'])->where('key', $requestField['key'])->first();
                if ($requestField['is_required']) {
                    if (!$fieldSelect) {
                        return resultFunction("Err code OR-S: the " . $requestField['key'] . ' is required');
                    }
                    $orderInformationParams[] = $this->setParamOrderInformation($order, $fieldSelect);
                    $customerParams = $this->setParamCustomer($customerParams, $fieldSelect);
                } else {
                    if ($fieldSelect) {
                        $orderInformationParams[] = $this->setParamOrderInformation($order, $fieldSelect);
                        $customerParams = $this->setParamCustomer($customerParams, $fieldSelect);
                    }
                }
            }

            $productTypeMapping = ProductTypeMapping::with(['variant', 'recipe'])
                ->whereIn('id', array_column($data['orders'], 'product_type_mapping_id'))
                ->get();
            $orderDetailParams = [];
            foreach ($data['orders'] as $orderData) {
                $ptmSelect = $productTypeMapping->where('id', $orderData['product_type_mapping_id'])->first();
                if (!$ptmSelect) return resultFunction("Err code OR-S: product type mapping id " . $orderData['product_type_mapping_id'] . " not found");
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

            if ($customerParams['phone'] !== '') {
                $customerHistory = Customer::with([])
                    ->where('company_id', $company->id)
                    ->where('phone', $data)
                    ->first();
                if (!$customerHistory) {
                    Customer::insert($customerParams);
                }
            }


            OrderStatus::insert([
                'order_id' => $order->id,
                'title' => 'pending',
                "createdAt" => date("Y-m-d H:i:s"),
                "updatedAt" => date("Y-m-d H:i:s")
            ]);
            OrderInformation::insert($orderInformationParams);
            OrderDetail::insert($orderDetailParams);

            DB::commit();
            return resultFunction("Success to create order", true);
        } catch (\Exception $e) {
            return resultFunction("Err code OR-S catch: " . $e->getMessage());
        }
    }

    public function setParamOrderInformation($order, $fieldSelect) {
        return [
            "order_id" => $order->id,
            "key_information" => $fieldSelect['key'],
            "value_information" => $fieldSelect['value']
        ];
    }

    public function setParamCustomer($customerParam, $requestField) {
        if ($requestField['key'] === 'name') $customerParam['name'] = $requestField['value'];
        if ($requestField['key'] === 'email') $customerParam['email'] = $requestField['value'];
        if ($requestField['key'] === 'phone') $customerParam['phone'] = $requestField['value'];
        return $customerParam;
    }

    public function delete($id, $companyId) {
        try {
            DB::beginTransaction();
            $order =  Order::find($id);
            if (!$order) return resultFunction('Err code OR-D: order not found');
            if ($order->company_id != $companyId) return resultFunction('Err code OR-D: order not found');
            $order->delete();

            OrderDetail::where('order_id', $id)->delete();
            OrderInformation::where('order_id', $id)->forceDelete();
            DB::commit();
            return resultFunction("Success to delete order", true);
        } catch (\Exception $e) {
            return resultFunction("Err code OR-D catch: " . $e->getMessage());
        }
    }

    public function detail($id, $companyId) {
        try {
            $order =  Order::with(['order_details', 'order_informations'])->find($id);
            if (!$order) return resultFunction('Err code OR-De: order not found');
            if ($order->company_id != $companyId) return resultFunction('Err code OR-De: order not found');
            return resultFunction("Success to delete order", true, $order);
        } catch (\Exception $e) {
            return resultFunction("Err code OR-De catch: " . $e->getMessage());
        }
    }

    public function saveFuHistory($data)
    {
        try {
            DB::beginTransaction();
            $validator = Validator::make($data, [
                'order_id' => 'required',
                'title' => 'required',
                'description' => 'required',
            ]);
            if ($validator->fails()) return resultFunction('Err OR-SFH: validation err ' . $validator->errors());

            $order = Order::find($data['order_id']);
            if (!$order) return resultFunction('Err code OR-SFH: order not found');

            $orderFuHistory = new OrderFuHistory();
            $orderFuHistory->order_id = $order->id;
            $orderFuHistory->title = $data['title'];
            $orderFuHistory->description = $data['description'];
            $orderFuHistory->save();
            DB::commit();
            return resultFunction("Success to save fu history", true);
        } catch (\Exception $e) {
            return resultFunction("Err code OR-SFH catch: " . $e->getMessage());
        }
    }

    public function indexFuHistory($id)
    {
        $orderFuHistory = OrderFuHistory::with([]);
        $orderFuHistory = $orderFuHistory->where('order_id', $id);
        $orderFuHistory = $orderFuHistory->orderBy('id', 'desc')->get();
        return $orderFuHistory;
    }

    public function saveStatus($data)
    {
        try {
            DB::beginTransaction();
            $validator = Validator::make($data, [
                'order_id' => 'required',
                'title' => 'required',
            ]);
            if ($validator->fails()) return resultFunction('Err OR-SFH: validation err ' . $validator->errors());

            $order = Order::find($data['order_id']);
            if (!$order) return resultFunction('Err code OR-SFH: order not found');

            if (in_array($data['title'], ['paid', 'unpaid'])) {
                $order->paid_at = $data['title'] === 'paid' ? date("Y-m-d H:i:s"): null;
            } else {
                $orderStatus = new OrderStatus();
                $orderStatus->order_id = $order->id;
                $orderStatus->title = $data['title'];
                $orderStatus->save();

                $order->last_status = $data['title'];
            }
            $order->save();

            DB::commit();
            return resultFunction("Success to save status", true);
        } catch (\Exception $e) {
            return resultFunction("Err code OR-SFH catch: " . $e->getMessage());
        }
    }

    public function indexStatus($id)
    {
        $indexStatus = OrderStatus::with([]);
        $indexStatus = $indexStatus->where('order_id', $id);
        $indexStatus = $indexStatus->orderBy('id', 'desc')->get();
        return $indexStatus;
    }
}