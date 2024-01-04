<?php

namespace App\Repositories;

use App\Models\OcCustomer;
use App\Models\OcInvoice;
use App\Models\OcOrder;
use App\Models\OcStatus;
use App\Models\OcStore;
use App\Models\TokpedProduct;
use Illuminate\Support\Facades\DB;

class TokopediaApiRepository
{
    protected $appId = "";

    public function __construct()
    {
        $this->appId = env("TOKPED_APP_ID");
    }

    public function indexCategory($filters = [])
    {
        try {
            $getData = (new GuzzleRepository())->getData($filters, "https://fs.tokopedia.net/inventory/v1/fs/" . $this->appId . "/product/category");
            if (!$getData['status']) return $getData;

            $result = [];
            foreach ($getData['data']['data']['categories'] as $firstC) {
                try {
                    if ($firstC->child) {
                        foreach ($firstC->child as $ch) {
                            try {
                                if ($ch->child) {
                                    foreach ($ch->child as $nc) {
                                        try {
                                            $nc->name = $firstC->name . ' / ' . $ch->name . ' / ' . $nc->name;
                                        } catch (\Exception $e) {
                                            return '';
                                        }
                                        $result[] = $nc;
                                    }
                                }
                            } catch (\Exception $e) {
                                return "";
                            }
                        }
                    }
                } catch (\Exception $e) {
                    $result[] = $firstC;
                }
            }

            return resultFunction("", true, $result);
        } catch (\Exception $e) {
            return resultFunction("Err code TAR-IC catch: " . $e->getMessage());
        }
    }

    public function createProduct($data)
    {
        try {
            $productData = $data['products'];
            $paramsRequest = [
                'products' => [
                    0 => [
                        'Name' => $productData['name'],
                        'condition' => $productData['condition'],
                        'Description' => $productData['description'],
                        'sku' => $productData['sku'],
                        'price' => (int)$productData['price'],
                        'status' => $productData['status'],
                        'stock' => (int)$productData['stock'],
                        'min_order' => (int)$productData['min_order'],
                        'category_id' => (int)$data['category_id']['id'],
                        'dimension' =>
                            [
                                'height' => 2,
                                'width' => 3,
                                'length' => 4,
                            ],
                        'custom_product_logistics' =>
                            [
                                0 => 24,
                                1 => 4,
                                2 => 64,
                            ],
                        'annotations' =>
                            [
                                0 => '1',
                            ],
                        'price_currency' => 'IDR',
                        'weight' => 200,
                        'weight_unit' => 'GR',
                        'is_free_return' => false,
                        'is_must_insurance' => false,
                        'etalase' =>
                            [
                                'id' => (int)$data['etalase_id']['id'],
                            ],
                        'pictures' =>
                            [
                                0 =>
                                    [
                                        'file_path' => 'https://ecs7.tokopedia.net/img/cache/700/product-1/2017/9/27/5510391/5510391_9968635e-a6f4-446a-84d0-ff3a98a5d4a2.jpg',
                                    ],
                            ],
                        'wholesale' =>
                            [
                                0 =>
                                    [
                                        'min_qty' => 2,
                                        'price' => 9500,
                                    ],
                                1 =>
                                    [
                                        'min_qty' => 3,
                                        'price' => 9000,
                                    ],
                            ],
                    ],
                ]
            ];
            $getData = (new GuzzleRepository())->getDataPost($paramsRequest, "https://fs.tokopedia.net/v3/products/fs/" . $this->appId . "/create?shop_id=" . $data['shop_id']['id']);
            if (!$getData['status']) return $getData;

            if ($getData['data']['data']['fail_data'] > 0) {
                return resultFunction("Err code TAR-IC: " . $getData['data']['data']['failed_rows_data'][0]['error'][0]);
            }

            $data['product_id'] = $getData['data']['data']['success_rows_data'][0]['product_id'];
            TokpedProduct::create($data);

            return $getData;
        } catch (\Exception $e) {
            return resultFunction("Err code TAR-IC catch: " . $e->getMessage());
        }
    }

    public function getShopInfo()
    {
        try {
            $getData = (new GuzzleRepository())->getData([], "https://fs.tokopedia.net/v1/shop/fs/" . $this->appId . "/shop-info");
            if (!$getData['status']) return $getData;

            return resultFunction("", true, $getData['data']['data']);
        } catch (\Exception $e) {
            return resultFunction("Err code TAR-IC catch: " . $e->getMessage());
        }
    }

    public function getShowcase($shopId)
    {
        try {
            $getData = (new GuzzleRepository())->getData([
                "shop_id" => $shopId
            ], "https://fs.tokopedia.net/v1/showcase/fs/" . $this->appId . "/get");
            if (!$getData['status']) return $getData;

            return resultFunction("", true, $getData['data']['data']);
        } catch (\Exception $e) {
            return resultFunction("Err code TAR-IC catch: " . $e->getMessage());
        }
    }

    public function indexProduct()
    {
        $tokpedProducts = TokpedProduct::orderBy('_id', 'desc')->get();
        return $tokpedProducts;
    }

    public function deleteProduct($id)
    {
        try {
            $product = TokpedProduct::find($id);

            $getData = (new GuzzleRepository())->getDataPost([
                'product_id' => [$product->product_id]
            ], "https://fs.tokopedia.net/v3/products/fs/" . $this->appId . "/delete?shop_id=" . $product->shop_id['id']);
            $product->delete();
            return resultFunction("", true);
        } catch (\Exception $e) {
            return resultFunction("Err code TAR-IC catch: " . $e->getMessage());
        }
    }

    public function detailProduct($id)
    {
        try {
            $product = TokpedProduct::find($id);

            $getData = (new GuzzleRepository())->getData([], "https://fs.tokopedia.net/inventory/v1/fs/" . $this->appId . "/product/info?product_id=" . $product->product_id);
            return $getData;
        } catch (\Exception $e) {
            return resultFunction("Err code TAR-IC catch: " . $e->getMessage());
        }
    }

    public function detailOrder($orderId)
    {
        try {
            $getData = (new GuzzleRepository())->getData([], "https://fs.tokopedia.net/v2/fs/" . $this->appId . "/order?order_id=" . $orderId);
            return $getData;
        } catch (\Exception $e) {
            return resultFunction("Err code TAR-IC catch: " . $e->getMessage());
        }
    }

    public function indexOrder($filters = [])
    {
        try {
            $fromDate = date("Y-m-d") . ' 00:00:00';
            $fromDate = strtotime($fromDate);
            $toDate = date("Y-m-d") . ' 23:59:59';
            $toDate = strtotime($toDate);
            $getData = (new GuzzleRepository())->getData($filters,
                "https://fs.tokopedia.net/v2/order/list?fs_id=" . $this->appId . "&shop_id=15618836&from_date=" . $fromDate . "&to_date=" . $toDate . "&page=1&per_page=1");
            return $getData;
        } catch (\Exception $e) {
            return resultFunction("Err code TAR-IC catch: " . $e->getMessage());
        }
    }

    public function webhookOrderNotification($data, $orderHistories = [])
    {
        try {
            DB::beginTransaction();

            $ocStore = OcStore::firstOrNew(['store_id' => $data['shop_id']]);
            $ocStore->store_type = 'tokopedia';
            $ocStore->type_logo = 'https://ik.imagekit.io/dbp/tokopedia-38845.png?updatedAt=1689581233543';
            $ocStore->store_name = $data['shop_name'];
            $ocStore->save();

            $ocCustomer = OcCustomer::firstOrNew(['customer_id' => $data['customer']['id']]);
            $ocCustomer->store_id = $ocStore->store_id;
            $ocCustomer->name = $data['customer']['name'];
            $ocCustomer->email = $data['customer']['email'];
            $ocCustomer->phone = $data['customer']['phone'];
            $ocCustomer->save();

            $ocInvoice = OcInvoice::firstOrNew(['invoice_ref_num' => $data['invoice_ref_num']]);
            $ocInvoice->payment_id = $data['payment_id'];
            $ocInvoice->store_id = $ocStore->store_id;
            $ocInvoice->order_id = $data['order_id'];
            $ocInvoice->customer_id = $data['customer']['id'];
            $ocInvoice->invoice_ref_num = $data['invoice_ref_num'];
            $ocInvoice->accept_deadline = $data['shipment_fulfillment']['accept_deadline'];
            $ocInvoice->confirm_shipping_deadline = $data['shipment_fulfillment']['confirm_shipping_deadline'];
            $ocInvoice->item_delivered_deadline = isset($data['shipment_fulfillment']['item_delivered_deadline']) ?? null;
            $ocInvoice->invoice_amount = $data['amt']['ttl_amount'];
            $ocInvoice->shipping_cost_amount = $data['amt']['shipping_cost'];
            $ocInvoice->insurance_cost_amount = $data['amt']['insurance_cost'];
            $ocInvoice->voucher_amount = $data['voucher_info']['voucher_amount'];
            $ocInvoice->voucher_info = $data['voucher_info']['voucher_code'];
            $ocInvoice->voucher_type = $data['voucher_info']['voucher_type'];
            $ocInvoice->shipping_agency = $data['logistics']['shipping_agency'];
            $ocInvoice->shipping_type = $data['logistics']['service_type'];
            $ocInvoice->shipping_geo = $data['recipient']['address']['geo'];
            $ocInvoice->recipient_city = $data['recipient']['address']['city'];
            $ocInvoice->recipient_district = $data['recipient']['address']['district'];
            $ocInvoice->recipient_province = $data['recipient']['address']['province'];
            $ocInvoice->recipient_postal_code = $data['recipient']['address']['postal_code'];
            $ocInvoice->payment_date = $data['payment_date'];
            $ocInvoice->save();


            foreach ($data['products'] as $item) {
                $ocOrder = OcOrder::firstOrNew([
                    "order_id" => $data['order_id'],
                    "product_id" => $item['id']
                ]);
                $ocOrder->store_id = $ocStore->store_id;
                $ocOrder->customer_id = $data['customer']['id'];
                $ocOrder->order_id = $data['order_id'];
                $ocOrder->invoice_ref_num = $data['invoice_ref_num'];
                $ocOrder->product_id = $item['id'];
                $ocOrder->product_name = $item['name'];
                $ocOrder->product_image_url = "";
                $ocOrder->product_sku = $item['sku'];
                $ocOrder->product_price = $item['price'];
                $ocOrder->platform_discount = 0;
                $ocOrder->seller_discount = 0;
                $ocOrder->product_sale_price = $item['price'];
                $ocOrder->product_quantity = $item['quantity'];
                $ocOrder->sub_total = $item['total_price'];
                $ocOrder->save();
            }

            if (count($orderHistories) === 0) {
                $ocStatus = OcStatus::firstOrNew(['order_id' => $data['order_id']]);
                $ocStatus->store_id = $ocStore->store_id;
                $ocStatus->status_code = $data['order_status'];
                $ocStatus->status_description = $this->statusCode($data['order_status']);
                $ocStatus->save();
            } else {
                foreach ($orderHistories as $history) {
                    $ocStatus = new OcStatus();
                    $ocStatus->store_id = $ocStore->store_id;
                    $ocStatus->order_id = $data['order_id'];
                    $ocStatus->status_code = $history['hist_status_code'];
                    $ocStatus->status_description = $this->statusCode($history['hist_status_code']);
                    $ocStatus->createdAt = date("Y-m-d H:i:s", strtotime($history['timestamp']));
                    $ocStatus->updatedAt = date("Y-m-d H:i:s", strtotime($history['timestamp']));
                    $ocStatus->save();
                }
            }

            DB::commit();
            return resultFunction("", true);
        } catch (\Exception $e) {
            return resultFunction("Err code TAR-WNC catch: " . $e->getMessage());
        }
    }

    public function statusCode($code)
    {
        $result = [];
        $result[0]= "Seller cancel order.";
        $result[3]= "Order Reject Due Empty Stock.";
        $result[5]= "Order Canceled by Fraud";
        $result[6]= "Order Rejected (Auto Cancel Out of Stock)";
        $result[10]= "Order rejected by seller.";
        $result[15]= "Instant Cancel by Buyer.";
        $result[100]= "Order Created.";
        $result[103]= "Wait for payment confirmation from third party.";
        $result[190]= "Confirmed payment from third party.";
        $result[220]= "Payment verified, order ready to process.";
        $result[221]= "Waiting for partner approval.";
        $result[400]= "Seller accept order.";
        $result[450]= "Waiting for pickup.";
        $result[500]= "Order shipment.";
        $result[501]= "Status changed to waiting resi have no input.";
        $result[520]= "Invalid shipment reference number (AWB).";
        $result[530]= "Requested by user to correct invalid entry of shipment reference number.";
        $result[540]= "Delivered to Pickup Point.";
        $result[550]= "Return to Seller.";
        $result[600]= "Order delivered.";
        $result[601]= "Buyer open a case to finish an order.";
        $result[690]= "Fraud Review";
        $result[700]= "Order finished";

        return $result[$code];
    }

    public function webhookOrderStatus($data)
    {
        try {
            $ocOrder = OcOrder::with([])->where('order_id', $data['order_id'])->first();

            $orderApi = $this->detailOrder($data['order_id']);
            $orderApiData = $orderApi['data']['data'];
            if (!$ocOrder) {
                $products = [];
                foreach ($orderApiData['order_info']['order_detail'] as $datum) {
                    $products[] = [
                        "id" => $datum['product_id'],
                        "name" => $datum['product_name'],
                        "sku" => $datum['sku'],
                        "price" => $datum['product_price'],
                        'quantity' => $datum['quantity'],
                        'total_price' => $datum['subtotal_price']
                    ];
                }
                $dataSave = [
                    "shop_id" => $orderApiData['shop_info']['shop_id'],
                    "shop_name" => $orderApiData['shop_info']['shop_name'],
                    "customer" => [
                        "id" => $this->getCustomerIdFromInvoiceUrl($orderApiData['invoice_url'], $orderApiData['seller_id']),
                        "name" => $orderApiData['buyer_info']['buyer_fullname'],
                        "email" => $orderApiData['buyer_info']['buyer_email'],
                        "phone" => $orderApiData['buyer_info']['buyer_phone'],
                    ],
                    'invoice_ref_num' => $orderApiData['invoice_number'],
                    'payment_id' => $orderApiData['payment_id'],
                    'order_id' => $orderApiData['order_id'],
                    'shipment_fulfillment' => $orderApiData['shipment_fulfillment'],
                    'amt' => [
                        "ttl_amount" => $orderApiData['open_amt'],
                        "shipping_cost" => $orderApiData['order_info']['shipping_info']['shipping_price'],
                        "insurance_cost" => $orderApiData['order_info']['shipping_info']['insurance_price']
                    ],
                    'voucher_info' => [
                        "voucher_amount" => 0,
                        "voucher_code" => "",
                        "voucher_type" => ""
                    ],
                    'logistics' => [
                        "shipping_agency" => $orderApiData['order_info']['shipping_info']['logistic_name'],
                        "service_type" => $orderApiData['order_info']['shipping_info']['logistic_service']
                    ],
                    "recipient" => [
                        "address" => [
                            "geo" => $orderApiData['origin_info']['destination_geo'],
                            "city" => $orderApiData['order_info']['destination']['address_city'],
                            "district" => $orderApiData['order_info']['destination']['address_district'],
                            "province" => $orderApiData['order_info']['destination']['address_province'],
                            "postal_code" => $orderApiData['order_info']['destination']['address_postal']
                        ]
                    ],
                    "payment_date" => $orderApiData['payment_date'],
                    "products" => $products,
                    "order_status" => $orderApiData['order_status']
                ];
                $this->webhookOrderNotification($dataSave, array_reverse($orderApiData['order_info']['order_history']));
            } else {
                $ocStatus = new OcStatus();
                $ocStatus->store_id = $orderApiData['shop_info']['shop_id'];
                $ocStatus->order_id = $orderApiData['order_id'];
                $ocStatus->status_code = $orderApiData['order_status'];
                $ocStatus->status_description = $this->statusCode($orderApiData['order_status']);
                $ocStatus->save();
            }

            return resultFunction("", true);
        } catch (\Exception $e) {
            return resultFunction("Err code TAR-WNC catch: " . $e->getMessage());
        }
    }

    public function getCustomerIdFromInvoiceUrl($url, $shopId) {
        try {
            return explode("-" . $shopId, (explode("Invoice-", $url))[1])[0];
        } catch (\Exception $e) {
            return 0;
        }
    }
}