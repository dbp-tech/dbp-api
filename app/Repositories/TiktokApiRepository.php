<?php

namespace App\Repositories;

use App\Models\OcCustomer;
use App\Models\OcInvoice;
use App\Models\OcOrder;
use App\Models\OcStatus;
use App\Models\OcStore;
use App\Models\TokpedProduct;
use Illuminate\Support\Facades\DB;

class TiktokApiRepository
{
    public function orderDetail($orderId)
    {
        try {
            $guzzleRepo = new GuzzleRepository();
            $timestamp = strtotime(now());
            $accessToken = $guzzleRepo->getTiktokToken();
            $sign = $guzzleRepo->signatureAlgorithm($timestamp, '/api/orders/detail/query');
            $endpoint = 'https://open-api.tiktokglobalshop.com/api/orders/detail/query?app_key=' . env('TIKTOK_APP_KEY') . '&timestamp=' . $timestamp . '&sign=' . $sign . '&access_token=' . $accessToken . '&shop_id=' . env('TIKTOK_SHOP_ID');

            $params = "order_id_list=['" . $orderId . "']";
            $client = new \GuzzleHttp\Client();
            $response = $client->post($endpoint, [
                'body' => $params
            ]);
            $content = json_decode($response->getBody()->getContents(), true);

            return resultFunction("", true, $content);
        } catch (\Exception $e) {
            return resultFunction("Err code TAR-IC catch: " . $e->getMessage());
        }
    }

    public function shopDetail($shopId)
    {
        try {
            $guzzleRepo = new GuzzleRepository();
            $timestamp = strtotime(now());
            $accessToken = $guzzleRepo->getTiktokToken();
            $sign = $guzzleRepo->signatureAlgorithm($timestamp, '/api/shop/get_authorized_shop');
            $endpoint = 'https://open-api.tiktokglobalshop.com/api/shop/get_authorized_shop?app_key=' . env('TIKTOK_APP_KEY') . '&timestamp=' . $timestamp . '&sign=' . $sign . '&access_token=' . $accessToken . '&shop_id=' . $shopId;

            $client = new \GuzzleHttp\Client();
            $response = $client->get($endpoint);
            $content = json_decode($response->getBody()->getContents(), true);

            return resultFunction("", true, $content);
        } catch (\Exception $e) {
            return resultFunction("Err code TAR-IC catch: " . $e->getMessage());
        }
    }

    public function webhookOrderStatus($data)
    {
        try {
            $ocStore = OcStore::with([])
                ->where('store_id', $data['shop_id'])
                ->first();

            if (!$ocStore) {
                $shopDetail = $this->shopDetail($data['shop_id']);
                if (!$shopDetail['status']) return $shopDetail;
                $shopDetail = $shopDetail['data']['data']['shop_list'][0];

                $ocStore = new OcStore();
                $ocStore->store_id = $data['shop_id'];
                $ocStore->store_type = 'tiktok';
                $ocStore->type_logo = 'https://ik.imagekit.io/dbp/Tiktok-Shop-Color-Black-Logo-PNG.png?updatedAt=1689845791830';
                $ocStore->store_name = $shopDetail['shop_name'];
                $ocStore->save();
            }

            $orderDetail = $this->orderDetail($data['data']['order_id']);
            if (!$orderDetail['status']) return $orderDetail;
            $orderDetail = $orderDetail['data']['data']['order_list'][0];


            $ocCustomer = OcCustomer::firstOrNew(['customer_id' => $orderDetail['buyer_uid']]);
            $ocCustomer->store_id = $ocStore->store_id;
            $ocCustomer->name = $orderDetail['recipient_address']['name'];
            $ocCustomer->email = $orderDetail['buyer_email'];
            $ocCustomer->phone = $orderDetail['recipient_address']['phone'];
            $ocCustomer->save();

            $invoiceRefNum = $orderDetail['buyer_uid'] . '/' . $orderDetail['create_time'] . '/' . $orderDetail['order_id'];
            $ocInvoice = OcInvoice::firstOrNew(['invoice_ref_num' => $invoiceRefNum]);
            $ocInvoice->payment_id = $orderDetail['order_id'];
            $ocInvoice->store_id = $ocStore->store_id;
            $ocInvoice->order_id = $orderDetail['order_id'];
            $ocInvoice->customer_id = $orderDetail['buyer_uid'];
            $ocInvoice->invoice_ref_num = $invoiceRefNum;
            $ocInvoice->accept_deadline = null;
            $ocInvoice->confirm_shipping_deadline = null;
            $ocInvoice->item_delivered_deadline = isset($orderDetail['delivery_sla']) ?? null;
            $ocInvoice->invoice_amount = $orderDetail['payment_info']['total_amount'];
            $ocInvoice->shipping_cost_amount = $orderDetail['payment_info']['shipping_fee'];
            $ocInvoice->insurance_cost_amount = 0;
            $ocInvoice->voucher_amount = $orderDetail['payment_info']['seller_discount'];
            $ocInvoice->voucher_info = 'seller_discount';
            $ocInvoice->voucher_type = 'seller_discount';
            $ocInvoice->shipping_agency = isset($orderDetail['shipping_provider']) ?? null;
            $ocInvoice->shipping_type = $orderDetail['delivery_option'];
            $ocInvoice->shipping_geo = null;
            $ocInvoice->recipient_city = $orderDetail['recipient_address']['city'];
            $ocInvoice->recipient_district = $orderDetail['recipient_address']['district'];
            $ocInvoice->recipient_province = $orderDetail['recipient_address']['state'];
            $ocInvoice->recipient_postal_code = $orderDetail['recipient_address']['zipcode'];
            $ocInvoice->payment_date = isset($orderDetail['paid_time']) ? date("Y-m-d H:i:s", substr($orderDetail['paid_time'], 0, 10)) : null;
            $ocInvoice->save();

            foreach ($orderDetail['item_list'] as $item) {

                $ocOrder = OcOrder::firstOrNew([
                    "order_id" => $orderDetail['order_id'],
                    "product_id" => $item['product_id']
                ]);
                $ocOrder->store_id = $ocStore->store_id;
                $ocOrder->customer_id = $orderDetail['buyer_uid'];
                $ocOrder->order_id = $orderDetail['order_id'];
                $ocOrder->invoice_ref_num = $invoiceRefNum;
                $ocOrder->product_id = $item['product_id'];
                $ocOrder->product_name = $item['product_name'];
                $ocOrder->product_image_url = $item['sku_image'];
                $ocOrder->product_sku = $item['seller_sku'];
                $ocOrder->product_price = $item['sku_original_price'];
                $ocOrder->platform_discount = $item['sku_platform_discount_total'];
                $ocOrder->seller_discount = $item['sku_seller_discount'];
                $ocOrder->product_sale_price = $item['sku_sale_price'];
                $ocOrder->product_quantity = $item['quantity'];
                $ocOrder->sub_total = $item['quantity'];
                $ocOrder->save();
            }

            $reverseCodes = [
                [
                    "code" => 1,
                    "description" => "AFTERSALE_APPLYING",
                ],
                [
                    "code" => 2,
                    "description" => "AFTERSALE_REJECT_APPLICATION",
                ],
                [
                    "code" => 3,
                    "description" => "AFTERSALE_RETURNING",
                ],
                [
                    "code" => 4,
                    "description" => "AFTERSALE_BUYER_SHIPPED",
                ],
                [
                    "code" => 5,
                    "description" => "AFTERSALE_SELLER_REJECT_RECEIVE",
                ],
                [
                    "code" => 50,
                    "description" => "AFTERSALE_SUCCESS",
                ],
                [
                    "code" => 51,
                    "description" => "CANCEL_SUCCESS",
                ],
                [
                    "code" => 99,
                    "description" => "CLOSED",
                ],
                [
                    "code" => 100,
                    "description" => "COMPLETE",
                ]
            ];

            $reverseType = [
                [
                    "code" => 1,
                    "description" => "CANCEL",
                ],
                [
                    "code" => 2,
                    "description" => "REFUND",
                ],
                [
                    "code" => 3,
                    "description" => "RETURN_AND_REFUND",
                ],
                [
                    "code" => 4,
                    "description" => "REQUEST_CANCEL",
                ]
            ];

            $reverseUser = [
                [
                    "code" => 1,
                    "description" => "BUYER",
                ],
                [
                    "code" => 2,
                    "description" => "SELLER",
                ],
                [
                    "code" => 3,
                    "description" => "OPERATOR",
                ],
                [
                    "code" => 4,
                    "description" => "SYSTEM",
                ],
            ];

            $dataStatus = [
                'store_id' => $ocStore->store_id,
                'order_id' => $orderDetail['order_id'],
                'status_code' => '',
                'status_description' => '',
                'status_type' => '',
                'status_user' => '',
                'createdAt' => date("Y-m-d H:i:s"),
                'updatedAt' => date("Y-m-d H:i:s")
            ];
            if ($data['type'] === 1) {
                $dataStatus['status_code'] = $data['data']['order_status'];
                $dataStatus['status_description'] = $data['data']['order_status'];
                $dataStatus['status_type'] = 'Order Status Update';
                $dataStatus['status_user'] = 'SYSTEM';
            } elseif ($data['type'] === 2) {
                $statusFind = collect($reverseCodes)->where('code', $data['data']['reverse_order_status'])->first();
                $typeFind = collect($reverseType)->where('code', $data['data']['reverse_type'])->first();
                $userFind = collect($reverseUser)->where('code', $data['data']['reverse_user'])->first();

                $dataStatus['status_code'] = $data['data']['reverse_order_status'];
                $dataStatus['status_description'] = $statusFind['description'];
                $dataStatus['status_type'] = $typeFind['description'];
                $dataStatus['status_user'] = $userFind['description'];
            }
            OcStatus::insert($dataStatus);

            return resultFunction("", true);
        } catch (\Exception $e) {
            return resultFunction("Err code TAR-WNC catch: " . $e->getMessage());
        }
    }

    public function orderIndex($filters)
    {
        try {
            $guzzleRepo = new GuzzleRepository();
            $timestamp = strtotime(now());
            $accessToken = $guzzleRepo->getTiktokToken();
            $sign = $guzzleRepo->signatureAlgorithm($timestamp, '/api/orders/search');
            $endpoint = 'https://open-api.tiktokglobalshop.com/api/orders/search?app_key=' . env('TIKTOK_APP_KEY') . '&timestamp=' . $timestamp . '&sign=' . $sign . '&access_token=' . $accessToken . '&shop_id=' . env('TIKTOK_SHOP_ID');

            $params = "page_size=10&start_time=" . strtotime($filters['start_date'] . ' 00:00:00') . "&end_time=" . strtotime($filters['end_date'] . ' 23:59:59');
            $client = new \GuzzleHttp\Client();
            $response = $client->post($endpoint, [
                'body' => $params
            ]);
            $content = json_decode($response->getBody()->getContents(), true);
            foreach ($content['data']['order_list'] as $key => $orderList) {
                $dataOrder = $this->orderDetail($orderList['order_id']);
                if ($dataOrder['status']) {
                    $content['data']['order_list'][$key]['data'] = $dataOrder['data']['data']['order_list'][0];
                } else {
                    $content['data']['order_list'][$key]['data'] = null;
                }
            }

            return resultFunction("", true, $content);
        } catch (\Exception $e) {
            return resultFunction("Err code TAR-IC catch: " . $e->getMessage());
        }
    }
}