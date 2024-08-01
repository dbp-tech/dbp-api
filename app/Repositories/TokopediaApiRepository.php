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

    public function getShopInfo($shopId = null)
    {
        try {
            $getData = (new GuzzleRepository())->getData([], "https://fs.tokopedia.net/v1/shop/fs/" . $this->appId . "/shop-info?shop_id=" . $shopId);
            if (!$getData['status']) return $getData;

            return resultFunction("", true, $getData['data'][0]);
        } catch (\Exception $e) {
            return resultFunction("Err code TAR-IC catch: " . $e->getMessage());
        }
    }

    public function getIndexOrder($shopId = null, $startDate, $endDate)
    {
        try {
            $fromDate = strtotime($startDate);
            $toDate = strtotime($endDate);
            $getData = (new GuzzleRepository())->getData([], "https://fs.tokopedia.net/v2/order/list?fs_id=" . $this->appId . "&shop_id=" . $shopId .
            "&from_date=" . $fromDate . "&to_date=" . $toDate . "&page=1&per_page=10");
            if (!$getData['status']) return $getData;

            return resultFunction("", true, $getData['data']);
        } catch (\Exception $e) {
            return resultFunction("Err code TAR-IC catch: " . $e->getMessage());
        }
    }

    public function getSingleOrder($orderId = null)
    {
        try {
            $getData = (new GuzzleRepository())->getData([], "https://fs.tokopedia.net/v2/fs/" . $this->appId . "/order?order_id=" . $orderId);
            if (!$getData['status']) return $getData;

            return resultFunction("", true, $getData['data']);
        } catch (\Exception $e) {
            return resultFunction("Err code TAR-IC catch: " . $e->getMessage());
        }
    }

    public function getProductByShopId($shopId, $page)
    {
        try {
            $getData = (new GuzzleRepository())->getData([], "https://fs.tokopedia.net/inventory/v1/fs/" . $this->appId . "/product/info?shop_id=" . $shopId . '&page=' . $page . '&per_page=20');
            if (!$getData['status']) return $getData;

            return resultFunction("", true, $getData['data']);
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

    public function webhookOrderNotification($data)
    {
        try {
            $ecomRepo = new EcomRepository();
            $ecomRepo->saveOrderMarketplaceTokpedToDb($data);

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
            if (!$ocOrder) {
                $ecomRepo = new EcomRepository();
                $ecomRepo->saveOrderMarketplaceTokpedToDb($data);
            }
            OcStatus::create([
                'store_id' => $data['shop_id'],
                'order_id' => $data['order_id'],
                'status_code' => $data['order_status'],
                'status_description' => $this->statusCode($data['order_status']),
                'status_type' => 'Order Status Update',
                'status_user' => 'SYSTEM'
            ]);
            
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