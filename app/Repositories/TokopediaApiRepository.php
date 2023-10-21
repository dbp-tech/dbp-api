<?php

namespace App\Repositories;

use App\Models\TokpedProduct;

class TokopediaApiRepository
{
    protected $appId = 17841;

    public function indexCategory($filters = [])
    {
        try {
            $getData = (new GuzzleRepository())->getData($filters, "https://fs.tokopedia.net/inventory/v1/fs/".$this->appId."/product/category");
            if (!$getData['status']) return $getData;

            $result = [];
            foreach ($getData['data']->data->categories as $firstC) {
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
                        'price' => (int) $productData['price'],
                        'status' => $productData['status'],
                        'stock' => (int) $productData['stock'],
                        'min_order' => (int) $productData['min_order'],
                        'category_id' => (int) $data['category_id']['id'],
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
                                'id' => (int) $data['etalase_id']['id'],
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
            $getData = (new GuzzleRepository())->getDataPost($paramsRequest, "https://fs.tokopedia.net/v3/products/fs/".$this->appId."/create?shop_id=". $data['shop_id']['id']);
            if (!$getData['status']) return $getData;

            if ($getData['data']['data']['fail_data'] > 0) {
                return resultFunction("Err code TAR-IC: " . $getData['data']['data']['failed_rows_data'][0]['error'][0]);
            }

            $data['product_id'] = $getData['data']['data']['success_rows_data'][0]['product_id'];
            TokpedProduct::create($data);

            return resultFunction("", true, $getData);
        } catch (\Exception $e) {
            return resultFunction("Err code TAR-IC catch: " . $e->getMessage());
        }
    }

    public function getShopInfo()
    {
        try {
            $getData = (new GuzzleRepository())->getData([], "https://fs.tokopedia.net/v1/shop/fs/".$this->appId."/shop-info");
            if (!$getData['status']) return $getData;

            return resultFunction("", true, $getData['data']->data);
        } catch (\Exception $e) {
            return resultFunction("Err code TAR-IC catch: " . $e->getMessage());
        }
    }

    public function getShowcase($shopId)
    {
        try {
            $getData = (new GuzzleRepository())->getData([
                "shop_id" => $shopId
            ], "https://fs.tokopedia.net/v1/showcase/fs/".$this->appId."/get");
            if (!$getData['status']) return $getData;

            return resultFunction("", true, $getData['data']->data);
        } catch (\Exception $e) {
            return resultFunction("Err code TAR-IC catch: " . $e->getMessage());
        }
    }

    public function indexProduct() {
        $tokpedProducts = TokpedProduct::orderBy('_id', 'desc')->get();
        return $tokpedProducts;
    }

    public function deleteProduct($id) {
        try {
            $product = TokpedProduct::find($id);

            $getData = (new GuzzleRepository())->getDataPost([
                'product_id' => [$product->product_id]
            ], "https://fs.tokopedia.net/v3/products/fs/".$this->appId."/delete?shop_id=". $product->shop_id['id']);
            $product->delete();
            return resultFunction("", true);
        } catch (\Exception $e) {
            return resultFunction("Err code TAR-IC catch: " . $e->getMessage());
        }
    }

    public function detailProduct($id) {
        try {
            $product = TokpedProduct::find($id);

            $getData = (new GuzzleRepository())->getData([], "https://fs.tokopedia.net/inventory/v1/fs/".$this->appId."/product/info?product_id=". $product->product_id);
            return resultFunction("", true, $getData);
        } catch (\Exception $e) {
            return resultFunction("Err code TAR-IC catch: " . $e->getMessage());
        }
    }

    public function indexOrder($filters = [])
    {
        try {
            $fromDate = '2023-10-01 00:00:00';
            $fromDate = strtotime($fromDate);
            $toDate = date("Y-m-d") . ' 23:59:59';
            $toDate = strtotime($toDate);
            $getData = (new GuzzleRepository())->getData($filters,
                "https://fs.tokopedia.net/v2/order/list?fs_id=".$this->appId."&shop_id=15618836&from_date=".$fromDate."&to_date=".$toDate."&page=1&per_page=1");
            if (!$getData['status']) return $getData;

            return resultFunction("", true, $getData);
        } catch (\Exception $e) {
            return resultFunction("Err code TAR-IC catch: " . $e->getMessage());
        }
    }
}