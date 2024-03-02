<?php

namespace App\Repositories;

use App\Models\OcOrder;
use App\Models\OcStore;
use Illuminate\Support\Facades\Cache;

class MarketplaceRepository
{
    public function indexStores() {
        return OcStore::paginate(10);
    }

    public function indexOrders($filters) {
        $ocOrders = OcOrder::with(['oc_store']);
        if(!empty($filters["store"])) {
            $ocOrders = $ocOrders->whereHas("oc_store", function($q) use($filters) {
                return $q->where('store_name', 'LIKE', '%'.$filters["store"].'%');
            });
        }

        if(!empty($filters["invoice_number"])) {
            $ocOrders = $ocOrders->where('invoice_ref_num', 'LIKE', '%'.$filters["invoice_number"].'%');
        }

        return $ocOrders
            ->orderByDesc("id")
            ->paginate(10);
    }

    public function detailOrder($id) {
        try {
            $ocOrder = OcOrder::with(['oc_store'])
            ->find($id);

            if (!$ocOrder) return resultFunction('Err MR-DO: Detail Order not found');


            if (Cache::has("detail-order-$id")) {
                return Cache::get("detail-order-$id");
            }

            $data = collect([
                'marketplace_type'  => ucfirst($ocOrder->oc_store->store_type),
                'marketplace_image' => $ocOrder->oc_store->type_logo
            ]);

            $productInfos = collect([]);
            $shippingInfos = collect([]);
            $invoiceDetail = collect([]);

            if (strtolower($ocOrder->oc_store->store_type) == "tokopedia") {
                $response = (new TokopediaApiRepository())->detailOrder($ocOrder->order_id);
                if ($response["status"]) {
                    $response = $response["data"]["data"];
                    foreach($response["order_info"]["order_detail"] as $orderDetail) {
                        $productInfos->push([
                            "product_id" => $orderDetail["product_id"],
                            "product_name" => $orderDetail["product_name"],
                            "product_image" => $orderDetail["product_picture"],
                            "product_price" => $orderDetail["product_price"],
                            "quantity" => $orderDetail["quantity"]
                        ]);
                    }

                    $shippingInfos->put("shipping_name", $response["order_info"]["shipping_info"]["logistic_name"] ."(".$response["order_info"]["shipping_info"]["logistic_service"].")");
                    $shippingInfos->put("awb", $response["order_info"]["shipping_info"]["awb"]);
                    $invoiceDetail->put("invoice", $response["invoice_number"]);
                    $invoiceDetail->put("total_price", $ocOrder->sub_total);
                }
            } else {
                $response = (new TiktokApiRepository())->orderDetail($ocOrder->order_id);
                if($response["status"] && $response["data"]["code"] == 0) {
                    $response = $response["data"]["data"];
                    foreach($response["order_list"] as $orderList) {
                        foreach($orderList["item_list"] as $itemList) {
                            $productInfos->push([
                                "product_id" => $itemList["product_id"],
                                "product_name" => $itemList["product_name"],
                                "product_image" => $itemList["sku_image"],
                                "product_price" => $itemList["sku_original_price"],
                                "quantity" => $itemList["quantity"]
                            ]);

                            $shippingInfos->put("shipping_name", $orderList["shipping_provider"] ?? $orderList["delivery_option_description"]);
                            $shippingInfos->put("awb", $orderList["shipping_provider_id"] ?? "-");
                        }
                    }
                }

                $invoiceDetail->put("invoice", $ocOrder->invoice_ref_num);
                $totalPrice = \DB::table('oc_orders')
                    ->where('order_id', $ocOrder->order_id)
                    ->sum(\DB::raw('product_price * product_quantity'));

                $invoiceDetail->put("invoice", $ocOrder->invoice_ref_num);
                $invoiceDetail->put("total_price", "Rp. " . $totalPrice);
            }


            $data->put("product_info", $productInfos->toArray());
            $data->put("shipping_info", $shippingInfos->toArray());
            $data->put("invoice_detail", $invoiceDetail->toArray());

            return Cache::remember("detail-order-$id" , now()->addMinutes(5) , function () use($data) {
                return resultFunction("Detail order Marketplace", true, $data->toArray());
            });
        } catch (\Throwable $th) {
            return resultFunction('Exception MR-DO: ' . $th->getMessage());
        }
    }
}
