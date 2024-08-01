<?php

namespace App\Repositories;

use App\Models\OcCustomer;
use App\Models\OcInvoice;
use App\Models\OcOrder;
use App\Models\OcStatus;
use App\Models\OcStore;

class TiktokApiRepository
{
    public function orderDetail($orderId, $shopId = null)
    {
        try {
            $guzzleRepo = new GuzzleRepository();
            $timestamp = strtotime("-60 seconds");
            $accessToken = $guzzleRepo->getTiktokToken();
            $dataParam = [
                'shop_id' => $shopId,
            ];
            $sign = $guzzleRepo->signatureAlgorithm($timestamp, '/api/orders/detail/query', $dataParam);
            $endpoint = 'https://open-api.tiktokglobalshop.com/api/orders/detail/query?app_key=' . env('TIKTOK_APP_KEY') . '&timestamp=' 
                . $timestamp . '&sign=' . $sign . '&access_token=' . $accessToken . '&shop_id=' . $shopId;

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

    public function orderDetailV2($orderId, $shopCipher)
    {
        try {
            $guzzleRepo = new GuzzleRepository();
            $timestamp = strtotime("-120 seconds");
            $accessToken = $guzzleRepo->getTiktokToken();
            $dataParam = [
                'shop_cipher' => $shopCipher,
                'ids' => [$orderId]
            ];
            $sign = $guzzleRepo->signatureAlgorithm($timestamp, '/api/order/202309/orders', $dataParam);
            $endpoint = 'https://open-api.tiktokglobalshop.com/order/202309/orders?app_key=' . env('TIKTOK_APP_KEY') . '&timestamp=' 
                . $timestamp . '&sign=' . $sign . '&shop_cipher=' . $shopCipher . '&ids=' . $orderId;

            $headers = [
                'Content-Type' => 'application/json',
                'x-tts-access-token' => $accessToken
            ];
            $client = new \GuzzleHttp\Client([
                'headers' => $headers
            ]);
            $response = $client->get($endpoint);
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
            $timestamp = strtotime("-60 seconds");
            $accessToken = $guzzleRepo->getTiktokToken();
            $dataParam = [
                'shop_id' => $shopId,
            ];
            $sign = $guzzleRepo->signatureAlgorithm($timestamp, '/api/shop/get_authorized_shop', $dataParam);
            $endpoint = 'https://open-api.tiktokglobalshop.com/api/shop/get_authorized_shop?app_key=' . env('TIKTOK_APP_KEY') . '&timestamp=' . $timestamp . '&sign=' . $sign . '&access_token=' . $accessToken . '&shop_id=' . $shopId;

            $client = new \GuzzleHttp\Client();
            $response = $client->get($endpoint);
            $content = json_decode($response->getBody()->getContents(), true);

            return resultFunction("", true, $content['data']['shop_list'][0]);
        } catch (\Exception $e) {
            return resultFunction("Err code TAR-IC catch: " . $e->getMessage());
        }
    }

    public function listProductByShopId($ocStore)
    {
        try {
            $details = json_decode($ocStore->attribute_details, true);
            $shopId = $details['shop_id'];
            $guzzleRepo = new GuzzleRepository();
            $timestamp = strtotime("-60 seconds");
            $accessToken = $guzzleRepo->getTiktokToken();
            $dataParam = [
                'shop_id' => (int) $shopId,
            ];
            $sign = $guzzleRepo->signatureAlgorithm($timestamp, '/api/products/search', $dataParam);

            $endpoint = 'https://open-api.tiktokglobalshop.com/api/products/search?app_key=' . env('TIKTOK_APP_KEY') . 
            '&timestamp=' . $timestamp . '&sign=' . $sign . '&access_token=' . $accessToken . '&shop_id=' . $shopId;

            $client = new \GuzzleHttp\Client();
            $response = $client->post($endpoint, [
                'body' => "page_number=1&page_size=20"
            ]);
            $content = json_decode($response->getBody()->getContents(), true);

            return resultFunction("", true, $content['data']['products']);
        } catch (\Exception $e) {
            return resultFunction("Err code TAR-IC catch: " . $e->getMessage());
        }
    }

    public function webhookOrderStatus($data)
    {
        try {
            $getDetail = $this->orderDetail($data['data']['order_id'], $data['shop_id']);
            if (!$getDetail['status']) return $getDetail;
            
            $ecomRepo = new EcomRepository();
            $resp = $ecomRepo->saveOrderMarketplaceTiktokToDb($getDetail['data']['data']['order_list'][0], $data['shop_id']);

            return resultFunction("", true);
        } catch (\Exception $e) {
            return resultFunction("Err code TAR-WNC catch: " . $e->getMessage());
        }
    }

    public function orderIndex($filters, $shopId)
    {
        try {
            $guzzleRepo = new GuzzleRepository();
            $timestamp = strtotime("-60 seconds");
            $accessToken = $guzzleRepo->getTiktokToken();
            $dataParam = [
                'shop_id' => $shopId,
            ];
            $sign = $guzzleRepo->signatureAlgorithm($timestamp, '/api/orders/search', $dataParam);
            $endpoint = 'https://open-api.tiktokglobalshop.com/api/orders/search?app_key=' . env('TIKTOK_APP_KEY') . '&timestamp=' . $timestamp . '&sign=' . $sign . '&access_token=' . $accessToken . '&shop_id=' . $shopId;

            $resultData = [];
            $params = "page_size=10&create_time_from=" . strtotime($filters['start_date'] . ' 00:00:00') . "&create_time_to=" . strtotime($filters['end_date'] . ' 23:59:59');
            $client = new \GuzzleHttp\Client();
            $response = $client->post($endpoint, [
                'body' => $params
            ]);
            $content = json_decode($response->getBody()->getContents(), true);
            if (isset($content['data']['order_list'])) {
                foreach ($content['data']['order_list'] as $key => $orderList) {
                    $dataOrder = $this->orderDetail($orderList['order_id'], $shopId);
                    if ($dataOrder['status']) {
                        $content['data']['order_list'][$key]['data'] = $dataOrder['data']['data']['order_list'][0];
                    } else {
                        $content['data']['order_list'][$key]['data'] = null;
                    }
                }
                $resultData = $content['data']['order_list'];
            } else {
                $content['data']['order_list'] = [];
                $resultData = [];
            }

            return resultFunction("", true, $resultData);
        } catch (\Exception $e) {
            return resultFunction("Err code TAR-IC catch: " . $e->getMessage());
        }
    }
}