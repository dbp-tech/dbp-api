<?php

namespace App\Repositories;

use App\Models\Company;
use App\Models\EcomInquiry;
use App\Models\EcomMasterFollowUp;
use App\Models\EcomMasterStatus;
use App\Models\EcomMasterStatusSub;
use App\Models\EcomProduct;
use App\Models\EcomProductCategory;
use App\Models\EcomProductCategoryMapping;
use App\Models\EcomProductMarketplaceMapping;
use App\Models\EcomProductStore;
use App\Models\EcomProductVariant;
use App\Models\OcCustomer;
use App\Models\OcInvoice;
use App\Models\OcOrder;
use App\Models\OcOrderItem;
use App\Models\OcPaymentDetail;
use App\Models\OcShippingDetail;
use App\Models\OcStatus;
use App\Models\OcStore;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class EcomRepository
{
    protected $tokpedRepo;
    protected $tiktokRepo;

    public function __construct()
    {
        $this->tokpedRepo = new TokopediaApiRepository();
        $this->tiktokRepo = new TiktokApiRepository();
    }

    public function categoryIndex($filters, $companyId)
    {
        $ecomProductCategory = EcomProductCategory::with([]);
        $ecomProductCategory = $ecomProductCategory->where('company_id', $companyId);
        $ecomProductCategory = $ecomProductCategory->orderBy('id', 'desc')->get();
        return $this->sortByParent(($ecomProductCategory));
    }

    public function sortByParent($ecomProductCategory) {
        $categoryResult = [];
        $mainCategories = $ecomProductCategory->whereNull('parent_id');
        foreach ($mainCategories as $mC) {
            $subCategories = $ecomProductCategory->where('parent_id', $mC->id);
            $subCategoryResult = [];
            if (count($subCategories) > 0) {
                $subSubCategoryResult = [];
                foreach ($subCategories as $sC) {
                    $subSubCategories = $ecomProductCategory->where('parent_id', $sC->id);
                    if (count($subSubCategories) > 0) {
                        foreach ($subSubCategories as $ssC) {
                            $subSubCategoryResult[] = [
                                'id' => $ssC->id,
                                'name' => $ssC->name,
                                'description' => $ssC->description,
                                'parent_id' => $ssC->parent_id,
                            ];
                        }
                    }
                    $subCategoryResult[] = [
                        'id' => $sC->id,
                        'name' => $sC->name,
                        'description' => $sC->description,
                        'parent_id' => $sC->parent_id,
                        'items' => $subSubCategoryResult
                    ];
                }
            }
            $categoryResult[] = [
                'id' => $mC->id,
                'name' => $mC->name,
                'description' => $mC->description,
                'parent_id' => $mC->parent_id,
                'items' => $subCategoryResult
            ];
        }
        return $categoryResult;
    }

    public function categorySave($data, $companyId)
    {
        try {
            $validator = Validator::make($data, [
                'name' => 'required',
                'description' => 'required'
            ]);
            if ($validator->fails()) return resultFunction('Err code ER-S: validation err ' . $validator->errors());

            $company = Company::find($companyId);
            if (!$company) return resultFunction('Err code ER-S: company not found');

            if ($data['id']) {
                $ecomProductCategory = EcomProductCategory::find($data['id']);
                if (!$ecomProductCategory) return resultFunction("Err code ER-S: product category not found");

            } else {
                $ecomProductCategory = new EcomProductCategory();
            }

            if ($data['parent_id']) {
                $parentCategory = EcomProductCategory::find($data['parent_id']);
                if (!$parentCategory) return resultFunction("Err code ER-S: parent of product category not found");

                if ($data['id']) {
                    if ($parentCategory->parent_id) {
                        $checkParentValid = $this->checkParentCategoryHasParentWhichIsMeAsChild($parentCategory, $ecomProductCategory);
                        if (!$checkParentValid['status']) return $checkParentValid;
                    }
                }

                $ecomProductCategory->parent_id = $parentCategory->id;
            }
            $ecomProductCategory->company_id = $company->id;
            $ecomProductCategory->name = $data['name'];
            $ecomProductCategory->description = $data['description'];
            if (isset($data['category_type'])) {
                $ecomProductCategory->category_type = $data['category_type'];
            }
            if (isset($data['attributes'])) {
                $ecomProductCategory->attributes = json_encode($data['attributes']);
            }
            $ecomProductCategory->save();

            return resultFunction("Success to create product category", true, $ecomProductCategory);
        } catch (\Exception $e) {
            return resultFunction("Err code ER-S catch: " . $e->getMessage());
        }
    }

    public function categoryDelete($id, $companyId) {
        try {
            $ecomProductCategory =  EcomProductCategory::find($id);
            if (!$ecomProductCategory) return resultFunction('Err ER-D: Sproduct category not found');

            if ($ecomProductCategory->company_id != $companyId) return resultFunction('Err code ER-CD: product category is not belongs you');

            $hasChild = EcomProductCategory::where('parent_id', $ecomProductCategory->id)
                ->first();
            if ($hasChild) return resultFunction("Err code ER-CD: the category has a child, please delete child first to remove this category");;

            $ecomProductCategory->delete();

            return resultFunction("Success to delete product category", true);
        } catch (\Exception $e) {
            return resultFunction("Err code ER-CD catch: " . $e->getMessage());
        }
    }

    public function checkParentCategoryHasParentWhichIsMeAsChild($parentCategory, $category) {
        try {
            // fungsi untuk mengecek parent category itu punya parent yang merupakan dia sendiri.
            // false ketika parent category terakhir tidak ditemukan
            $loops = true;
            $parentId = $parentCategory->parent_id;
            while ($loops) {
                $pCat = EcomProductCategory::find($parentId);
                if (!$pCat) return resultFunction("Err code ER-CPC: the product category not found");

                if ($pCat->id === $category->id) return resultFunction("Err code ER-CPC: the parent category is his self");

                if ($pCat->parent_id) {
                    $parentId = $pCat->parent_id;
                } else {
                    $loops = false;
                }
            }
            return resultFunction("", true);
        } catch (\Exception $e) {
            return resultFunction("Err code ER-CPC catch: " . $e->getMessage());
        }
    }

    public function productIndex($filters, $companyId)
    {
        $ecomProduct = EcomProduct::with(['product_variants', 'product_category.ecom_product_category',
            'ecom_product_store_many.store', 'ecom_checkout_forms']);
        $ecomProduct = $ecomProduct->where('company_id', $companyId);
        $ecomProduct = $ecomProduct->orderBy('id', 'desc')->get();
        return $ecomProduct;
    }

    public function productSave($data, $companyId)
    {
        try {
            DB::beginTransaction();

            $validator = Validator::make($data, [
                "category_id" => "required",
                'sku' => 'required',
                'name' => 'required',
                'description' => 'required',
                'price' => 'required',
                'currency' => 'required',
                'quantity' => 'required',
                'images' => 'required',
                'status' => 'required',
                'variants' => 'required',
            ]);
            if ($validator->fails()) return resultFunction('Err code ER-PS: validation err ' . $validator->errors());

            $company = Company::find($companyId);
            if (!$company) return resultFunction('Err code ER-PS: company not found');

            $ecomProductCategory = EcomProductCategory::find($data['category_id']);
            if (!$ecomProductCategory) return resultFunction('Err code ER-PS: company not found');

            if ($data['id']) {
                $ecomProduct = EcomProduct::find($data['id']);
                if (!$ecomProduct) return resultFunction("Err code ER-PS: product not found");

                EcomProductVariant::where('product_id', $ecomProduct->id)->delete();
                EcomProductCategoryMapping::where('product_id', $ecomProduct->id)->delete();
            } else {
                $ecomProduct = new EcomProduct();
            }

            $ecomProduct->company_id = $company->id;
            $ecomProduct->sku = $data['sku'];
            $ecomProduct->name = $data['name'];
            $ecomProduct->description = $data['description'];
            $ecomProduct->price = $data['price'];
            $ecomProduct->currency = $data['currency'];
            $ecomProduct->quantity = $data['quantity'];
            $ecomProduct->images = json_encode($data['images']);
            $ecomProduct->status = $data['status'];
            $ecomProduct->save();

            if (!$data['id']) {
                $storeDefault = OcStore::with([])
                    ->where('company_id', $companyId)
                    ->where('store_type', 'Default')
                    ->first();
                if (!$storeDefault) return resultFunction("Err code ER-PS: please create a store default before you create an product");

                $ecomProductStore = new EcomProductStore();
                $ecomProductStore->product_id = $ecomProduct->id;
                $ecomProductStore->store_id = $storeDefault->id;
                $ecomProductStore->save();
            }

            $ecomProductMapping = new EcomProductCategoryMapping();
            $ecomProductMapping->product_id  = $ecomProduct->id;
            $ecomProductMapping->category_id = $ecomProductCategory->id;
            $ecomProductMapping->save();

            $variantData = [];
            foreach ($data['variants'] as $variant) {
                $variantData['product_id'] = $ecomProduct->id;
                $variantData['variant_sku'] = $variant['variant_sku'];
                $variantData['attributes'] = json_encode($variant['attributes']);
                $variantData['price'] = $variant['price'];
                $variantData['quantity'] = $variant['quantity'];
                $variantData['images'] = json_encode($variant['images']);
                $variantData['createdAt'] = date("Y-m-d H:i:s");
                $variantData['updatedAt'] = date("Y-m-d H:i:s");
            }

            EcomProductVariant::insert($variantData);
            DB::commit();
            return resultFunction("Success to create product", true, $ecomProduct);
        } catch (\Exception $e) {
            return resultFunction("Err code ER-PS catch: " . $e->getMessage());
        }
    }

    public function productDelete($id, $companyId) {
        try {
            DB::beginTransaction();

            $ecomProduct =  EcomProduct::find($id);
            if (!$ecomProduct) return resultFunction('Err ER-PD: product not found');

            if ($ecomProduct->company_id != $companyId) return resultFunction('Err code ER-PD: product is not belongs you');

            EcomProductVariant::where('product_id', $ecomProduct->id)->delete();
            $ecomProduct->delete();

            DB::commit();
            return resultFunction("Success to delete product", true);
        } catch (\Exception $e) {
            return resultFunction("Err code ER-PD catch: " . $e->getMessage());
        }
    }

    public function storeIndex($filters, $companyId)
    {
        $ocStores = OcStore::with(['ecom_product_stores.product', 'ecom_product_stores.ecom_product_marketplace_mapping',
            'oc_orders']);
        $ocStores = $ocStores->where('company_id', $companyId);
        $ocStores = $ocStores->orderBy('id', 'desc')->get();
        return $ocStores;
    }

    public function storeSave($data, $companyId)
    {
        try {
            DB::beginTransaction();
            $validator = Validator::make($data, [
                'store_id' => 'required',
                'store_type' => 'required',
                'is_active' => 'required',
                'type_logo' => 'required',
                'store_name' => 'required'
            ]);
            if ($validator->fails()) return resultFunction('Err code ER-SS: validation err ' . $validator->errors());

            $company = Company::find($companyId);
            if (!$company) return resultFunction('Err code ER-SS: company not found');

            if ($data['id']) {
                $ocStore = OcStore::find($data['id']);
                if (!$ocStore) return resultFunction("Err code ER-SS: product category not found");
            } else {
                $ocStore = new OcStore();
            }
            $ocStore->company_id = $company->id;
            $ocStore->store_id = $data['store_id'];
            $ocStore->store_name = $data['store_name'];
            $ocStore->is_active = $data['is_active'];
            $ocStore->type_logo = $data['type_logo'];
            $ocStore->store_type = $data['store_type'];
            if (isset($data['attribute_details'])) {
                $ocStore->attribute_details = json_encode($data['attribute_details']);
            }
            $ocStore->save();

            // Check if creating one
            if (!$data['id']) {
                // Get all store
                $isOnlyOne = OcStore::with([])
                    ->where("company_id", $company->id)
                    ->get();
                // Check the store data is one means it is new one
                if (count($isOnlyOne) == 1) {
                    // Add status and follow up data
                    $this->storeMasterStatusDefault($companyId);
                }
            }

            DB::commit();
            return resultFunction("Success to create store", true, $ocStore);
        } catch (\Exception $e) {
            return resultFunction("Err code ER-SS catch: " . $e->getMessage());
        }
    }

    public function storeDelete($id, $companyId) {
        try {
            $ocStore =  OcStore::find($id);
            if (!$ocStore) return resultFunction('Err ER-SD: store not found');

            if ($ocStore->company_id != $companyId) return resultFunction('Err code ER-sD: store is not belongs you');

            $ocStore->delete();

            return resultFunction("Success to delete store", true);
        } catch (\Exception $e) {
            return resultFunction("Err code ER-SD catch: " . $e->getMessage());
        }
    }

    public function marketplaceDetail($id, $companyId) {
        try {
            $ocStore =  OcStore::find($id);
            if (!$ocStore) return resultFunction('Err ER-MD: store not found');

            if ($ocStore->company_id != $companyId) return resultFunction('Err code ER-MD: store is not belongs you');

            if (!$ocStore->store_id) return resultFunction('Err code ER-MD: store id not found');

            $response = [
                "name" => "",
                "image" => ""
            ];
            if ($ocStore->store_type === 'Tiktok') {
                $storeDetail = $this->tiktokRepo->shopDetail($ocStore->store_id);
                if (!$storeDetail['status']) return $storeDetail;
                $response['name'] = $storeDetail['data']['shop_name'];
            } else if ($ocStore->store_type === 'Tokopedia') {
                $storeDetail = $this->tokpedRepo->getShopInfo($ocStore->store_id);
                if (!$storeDetail['status']) return $storeDetail;
                $response['name'] = $storeDetail['data']['shop_name'];
                $response['image'] = $storeDetail['data']['logo'];
            }

            $ocStore->attribute_details = json_encode($storeDetail['data']);
            $ocStore->save();

            return resultFunction("", true, $response);
        } catch (\Exception $e) {
            return resultFunction("Err code ER-MD catch: " . $e->getMessage());
        }
    }

    public function marketplaceOrder($id, $body, $companyId) {
        try {
            $checkValid = $this->checkValidParamOfMutationOrder($body);
            if (!$checkValid['status']) return $checkValid;

            $ocStore =  OcStore::find($id);
            if (!$ocStore) return resultFunction('Err code ER-MO: store not found');

            if ($ocStore->company_id != $companyId) return resultFunction('Err code ER-MO: store is not belongs you');

            if (!$ocStore->store_id) return resultFunction('Err code ER-MO: store id not found');

            $response = [];
            if ($ocStore->store_type === 'Tiktok') {
                $storeDetail = $this->tiktokRepo->orderIndex($checkValid['data'], $ocStore->store_id);
                if (!$storeDetail['status']) return $storeDetail;
                foreach ($storeDetail['data'] as $item) {
                    $this->saveOrderMarketplaceTiktokToDb($item['data'], $ocStore->store_id);
                }
                $response = $storeDetail['data'];
            } else if ($ocStore->store_type === 'Tokopedia') {
                $getOrders = $this->getIndexOrderByDuration($checkValid, $ocStore);
                if (!$getOrders['status']) return $getOrders;

                foreach ($getOrders['data'] as $item) {
                    $this->saveOrderMarketplaceTokpedToDb($item);
                }
                $response = $getOrders['data'];
            }

            return resultFunction("", true, $response);
        } catch (\Exception $e) {
            return resultFunction("Err code ER-MO catch: " . $e->getMessage());
        }
    }

    public function getIndexOrderByDuration($checkValid, $ocStore) {
        try {
            $dataOrders = [];
            $startDate = $checkValid['data']['start_date'];
            for ($i = 0; $i < 30; $i++) {
                $nextDate = date("Y-m-d", strtotime("+3 days". $startDate));
                if ($nextDate > date("Y-m-d")) {
                    $endDate = date("Y-m-d");
                    $i = 30;
                } else {
                    $endDate = $nextDate;
                }
                $indexOrders = $this->tokpedRepo->getIndexOrder($ocStore->store_id, $startDate, $endDate);
                if (!$indexOrders['status']) return $indexOrders;
                $startDate = $nextDate;
                $dataOrders = array_merge($dataOrders, $indexOrders['data']);
            }

            return resultFunction("", true, $dataOrders);
        } catch (\Exception $e) {
            return resultFunction("Err code ER-GIO: catch " . $e->getMessage());
        }
    }

    public function checkValidParamOfMutationOrder($body) {
        try {
            if ($body['type'] == 'Custom') {
                if ($body['start_date'] === '' OR $body['end_date'] === '') return resultFunction("Err code ER-CVP: start or end date is empty for Custom type.");

                if ($body['end_date'] < $body['start_date']) return resultFunction("Err code ER-CVP: end date is less than start date");
            } else {
                $body['end_date'] = date("Y-m-d");
                if ($body['type'] === 'Yesterday') $body['start_date'] = date("Y-m-d", strtotime('-1 days'));
                if ($body['type'] === 'Last Week') $body['start_date'] = date("Y-m-d", strtotime('-7 days'));
            }

            return resultFunction("", true, $body);
        } catch (\Exception $e) {
            return resultFunction("Err code ER-CVP: catch " . $e->getMessage());
        }
    }

    public function saveOrderMarketplaceTiktokToDb($prodDet, $storeId) {
        try {
            DB::beginTransaction();
            $ocOrder = OcOrder::with([])
                ->where('order_id', $prodDet['order_id'])
                ->first();
            if (!$ocOrder) {
                $ocOrder = new OcOrder();
            }
            $ocOrder->order_id = $prodDet['order_id'];
            $invoiceRefNum = $prodDet['buyer_uid'] . ' ' . $prodDet['order_id'];;
            $ocOrder->customer_id = $prodDet['buyer_uid'];
            $ocOrder->store_id = $storeId;
            $ocOrder->invoice_ref_num = $invoiceRefNum;
            $ocOrder->product_id = $prodDet['item_list'][0]['product_id'];
            $ocOrder->product_name = $prodDet['item_list'][0]['product_name'];
            $ocOrder->product_image_url = $prodDet['item_list'][0]['sku_image'];
            $ocOrder->product_sku = $prodDet['item_list'][0]['sku_name'];
            $ocOrder->createdAt = date("Y-m-d H:i:s", substr($prodDet['create_time'], 0, 10));
            $ocOrder->updatedAt = date("Y-m-d H:i:s", substr($prodDet['update_time'], 0, 10));
            $ocOrder->save();

            OcOrderItem::where('order_id', $prodDet['order_id'])->delete();
            $orderItemDatas = [];
            $priceTotal = 0;
            $qtyTotal = 0;
            foreach ($prodDet['order_line_list'] as $key => $prod) {
                $orderItemDatas[] = [
                    'order_item_id' => $prod['order_line_id'],
                    'order_id' => $prodDet['order_id'],
                    'product_id' => $prod['product_id'],
                    'product_name' => $prod['product_name'],
                    'product_image_url' => $prod['sku_image'],
                    'product_sku' => $prod['sku_name'],
                    'product_details' => json_encode($prod),
                    'quantity' => $prodDet['item_list'][$key]['quantity'],
                    'price' => $prod['original_price'],
                    'discount' => $prod['platform_discount'],
                    'final_price' => $prod['sale_price'],
                    'createdAt' => date("Y-m-d H:i:s"),
                    'updatedAt' => date("Y-m-d H:i:s")
                ];
                $priceTotal = $priceTotal + $prod['sale_price'];
                $qtyTotal = $qtyTotal + $prodDet['item_list'][$key]['quantity'];
            }
            OcOrderItem::insert($orderItemDatas);
            $ocOrder->product_price = $priceTotal;
            $ocOrder->product_sale_price = $priceTotal;
            $ocOrder->product_quantity = $qtyTotal;
            $ocOrder->sub_total = $priceTotal;
            $ocOrder->save();

            $ocInvoice = OcInvoice::with([])
                ->where('order_id', $prodDet['order_id'])
                ->where('store_id', $storeId)
                ->where('payment_id', $prodDet['order_id'])
                ->first();
            if (!$ocInvoice) {
                $ocInvoice = new OcInvoice();
                $ocInvoice->payment_id = $prodDet['order_id'];
                $ocInvoice->store_id = $storeId;
                $ocInvoice->order_id = $prodDet['order_id'];
            }
            $ocInvoice->customer_id = $prodDet['buyer_uid'];
            $ocInvoice->invoice_ref_num = $invoiceRefNum;
            $ocInvoice->accept_deadline = null;
            $ocInvoice->confirm_shipping_deadline = null;
            $ocInvoice->item_delivered_deadline = $qtyTotal;
            $ocInvoice->invoice_amount = $prodDet['payment_info']['total_amount'];
            $ocInvoice->shipping_cost_amount = $prodDet['payment_info']['original_shipping_fee'];
            $ocInvoice->insurance_cost_amount = 0;
            $ocInvoice->voucher_amount = $prodDet['payment_info']['shipping_fee_platform_discount'];
            $ocInvoice->shipping_agency = null;
            $ocInvoice->shipping_type = 'STANDARD';
            $ocInvoice->shipping_geo = null;
            $ocInvoice->recipient_city = $prodDet['recipient_address']['city'];
            $ocInvoice->recipient_district = $prodDet['recipient_address']['district'];
            $ocInvoice->recipient_province = $prodDet['recipient_address']['state'];
            $ocInvoice->recipient_postal_code = $prodDet['recipient_address']['zipcode'];
            $ocInvoice->payment_date = null;
            $ocInvoice->save();

            $ocCustomer = OcCustomer::with([])
                ->where('store_id', $storeId)
                ->where('customer_id', $prodDet['buyer_uid'])
                ->first();
            if (!$ocCustomer) {
                $ocCustomer = new OcCustomer();
            }
            $ocCustomer->store_id = $storeId;
            $ocCustomer->customer_id = $prodDet['buyer_uid'];
            $ocCustomer->name = $prodDet['recipient_address']['name'];
            $ocCustomer->phone = $prodDet['recipient_address']['phone'];
            $ocCustomer->email = $prodDet['buyer_email'];
            $ocCustomer->save();

            $ocPaymentDetail = OcPaymentDetail::with([])
                ->where('order_id', $prodDet['order_id'])
                ->first();
            if (!$ocPaymentDetail) {
                $ocPaymentDetail = new OcPaymentDetail();
                $ocPaymentDetail->payment_id = $prodDet['order_id'];
                $ocPaymentDetail->order_id = $prodDet['order_id'];
            }
            $ocPaymentDetail->payment_date = null;
            $ocPaymentDetail->payment_method = "STANDARD";
            $ocPaymentDetail->amount_details = null;
            $ocPaymentDetail->save();

            $ocShippingDetail = OcShippingDetail::with([])
                ->where('order_id', $prodDet['order_id'])
                ->first();
            if (!$ocShippingDetail) {
                $ocShippingDetail = new OcShippingDetail();
                $ocShippingDetail->shipping_id = "";
                $ocShippingDetail->order_id = $prodDet['order_id'];
            }
            $ocShippingDetail->recipient_name = $prodDet['recipient_address']['name'];
            $ocShippingDetail->phone = $prodDet['recipient_address']['phone'];
            $ocShippingDetail->address_full = $prodDet['recipient_address']['full_address'];
            $ocShippingDetail->city = $prodDet['recipient_address']['city'];
            $ocShippingDetail->province = $prodDet['recipient_address']['state'];
            $ocShippingDetail->postal_code = $prodDet['recipient_address']['zipcode'];
            $ocShippingDetail->country = $prodDet['recipient_address']['region'];
            $ocShippingDetail->geo = null;
            $ocShippingDetail->shipping_agency = null;
            $ocShippingDetail->service_type = 'STANDARD';
            $ocShippingDetail->accept_deadline = null;
            $ocShippingDetail->confirm_shipping_deadline = null;
            $ocShippingDetail->shipping_details = json_encode($prodDet['recipient_address']);
            $ocShippingDetail->save();


            DB::commit();
            return resultFunction("", true);
        } catch (\Exception $e) {
            return resultFunction("Err code ER-SO: catch " . $e->getMessage());
        }
    }

    public function saveOrderMarketplaceTokpedToDb($data) {
        try {
            $singleOrder = $this->tokpedRepo->getSingleOrder($data['order_id']);
            if (!$singleOrder['status']) return $singleOrder;
            $prodDet = $singleOrder['data'];

            DB::beginTransaction();
            $ocOrder = OcOrder::with([])
                ->where('order_id', $data['order_id'])
                ->first();
            if (!$ocOrder) {
                $ocOrder = new OcOrder();
                $ocOrder->order_id = $prodDet['order_id'];
            }
            $ocOrder->customer_id = $prodDet['buyer_id'] ? $prodDet['buyer_id'] :  (isset($data['buyer']) ? $data['buyer']['id'] : null);
            $ocOrder->store_id = $prodDet['seller_id'];
            $ocOrder->invoice_ref_num = $prodDet['invoice_number'];
            $ocOrder->product_id = $prodDet['order_info']['order_detail'][0]['product_id'];
            $ocOrder->product_name = $prodDet['order_info']['order_detail'][0]['product_name'];
            $ocOrder->product_image_url = $prodDet['order_info']['order_detail'][0]['product_picture'];
            $ocOrder->product_sku = $prodDet['order_info']['order_detail'][0]['sku'];
            $ocOrder->createdAt = date("Y-m-d H:i:s", strtotime($prodDet['create_time']));
            $ocOrder->updatedAt = date("Y-m-d H:i:s", strtotime($prodDet['update_time']));
            $ocOrder->save();

            OcOrderItem::where('order_id', $prodDet['order_id'])->delete();
            $orderItemDatas = [];
            $priceTotal = 0;
            $qtyTotal = 0;
            foreach ($prodDet['order_info']['order_detail'] as $prod) {
                $orderItemDatas[] = [
                    'order_item_id' => $prod['order_detail_id'],
                    'order_id' => $prodDet['order_id'],
                    'product_id' => $prod['product_id'],
                    'product_name' => $prod['product_name'],
                    'product_image_url' => $prod['product_picture'],
                    'product_sku' => $prod['sku'],
                    'product_details' => json_encode($prod),
                    'quantity' => $prod['quantity'],
                    'price' => $prod['product_price'],
                    'discount' => 0,
                    'final_price' => $prod['product_price'],
                    'createdAt' => date("Y-m-d H:i:s"),
                    'updatedAt' => date("Y-m-d H:i:s")
                ];
                $priceTotal = $priceTotal + $prod['product_price'];
                $qtyTotal = $qtyTotal + $prod['quantity'];
            }
            OcOrderItem::insert($orderItemDatas);
            $ocOrder->product_price = $priceTotal;
            $ocOrder->product_sale_price = $priceTotal;
            $ocOrder->product_quantity = $qtyTotal;
            $ocOrder->sub_total = $priceTotal;
            $ocOrder->save();

            $ocInvoice = OcInvoice::with([])
                ->where('order_id', $data['order_id'])
                ->where('store_id', $prodDet['seller_id'])
                ->where('payment_id', $prodDet['payment_id'])
                ->first();
            if (!$ocInvoice) {
                $ocInvoice = new OcInvoice();
                $ocInvoice->payment_id = $prodDet['payment_id'];
                $ocInvoice->store_id = $prodDet['seller_id'];
                $ocInvoice->order_id = $prodDet['order_id'];
            }
            $ocInvoice->customer_id = $prodDet['buyer_id'] ? $prodDet['buyer_id'] :  (isset($data['buyer']) ? $data['buyer']['id'] : null);
            $ocInvoice->invoice_ref_num = $prodDet['invoice_number'];
            $ocInvoice->accept_deadline = $prodDet['shipment_fulfillment']['accept_deadline'];
            $ocInvoice->confirm_shipping_deadline = $prodDet['shipment_fulfillment']['confirm_shipping_deadline'];
            $ocInvoice->item_delivered_deadline = $qtyTotal;
            $ocInvoice->invoice_amount = $prodDet['open_amt'];
            $ocInvoice->shipping_cost_amount = $prodDet['order_info']['shipping_info']['shipping_price'];
            $ocInvoice->insurance_cost_amount = $prodDet['order_info']['shipping_info']['insurance_price'];
            $ocInvoice->shipping_agency = $prodDet['order_info']['shipping_info']['logistic_name'];
            $ocInvoice->shipping_type = $prodDet['order_info']['shipping_info']['logistic_service'];
            $ocInvoice->shipping_geo = $prodDet['origin_info']['destination_geo'];
            $ocInvoice->recipient_city = $prodDet['order_info']['destination']['address_city'];
            $ocInvoice->recipient_district = $prodDet['order_info']['destination']['address_district'];
            $ocInvoice->recipient_province = $prodDet['order_info']['destination']['address_province'];
            $ocInvoice->recipient_postal_code = $prodDet['order_info']['destination']['address_postal'];
            $ocInvoice->payment_date = $prodDet['payment_info']['payment_date'];
            $ocInvoice->save();

            $ocCustomer = OcCustomer::with([])
                ->where('store_id', $prodDet['seller_id'])
                ->where('customer_id', $prodDet['buyer_id'] ? $prodDet['buyer_id'] :  (isset($data['buyer']) ? $data['buyer']['id'] : null))
                ->first();
            if (!$ocCustomer) {
                $ocCustomer = new OcCustomer();
            }
            $ocCustomer->store_id = $prodDet['seller_id'];
            $ocCustomer->customer_id = $prodDet['buyer_id'] ? $prodDet['buyer_id'] :  (isset($data['buyer']) ? $data['buyer']['id'] : null);
            $ocCustomer->save();

            $ocPaymentDetail = OcPaymentDetail::with([])
                ->where('payment_id', $prodDet['payment_info']['payment_id'])
                ->where('order_id', $prodDet['order_id'])
                ->first();
            if (!$ocPaymentDetail) {
                $ocPaymentDetail = new OcPaymentDetail();
                $ocPaymentDetail->payment_id = $prodDet['payment_info']['payment_id'];
                $ocPaymentDetail->order_id = $prodDet['order_id'];
            }
            $ocPaymentDetail->payment_date = date("Y-m-d H:i:s", strtotime($prodDet['payment_info']['payment_date']));
            $ocPaymentDetail->payment_method = $prodDet['payment_info']['payment_method'];
            $ocPaymentDetail->amount_details = json_encode($prodDet['payment_info']);
            $ocPaymentDetail->save();

            $ocShippingDetail = OcShippingDetail::with([])
                ->where('shipping_id', $prodDet['order_info']['shipping_info']['shipping_id'])
                ->where('order_id', $prodDet['order_id'])
                ->first();
            if (!$ocShippingDetail) {
                $ocShippingDetail = new OcShippingDetail();
                $ocShippingDetail->shipping_id = $prodDet['order_info']['shipping_info']['shipping_id'];
                $ocShippingDetail->order_id = $prodDet['order_id'];
            }
            $ocShippingDetail->recipient_name = $prodDet['order_info']['destination']['receiver_name'];
            $ocShippingDetail->phone = $prodDet['order_info']['destination']['receiver_phone'];
            $ocShippingDetail->address_full = $prodDet['order_info']['destination']['address_street'];
            $ocShippingDetail->city = $prodDet['order_info']['destination']['address_city'];
            $ocShippingDetail->province = $prodDet['order_info']['destination']['address_province'];
            $ocShippingDetail->postal_code = $prodDet['order_info']['destination']['address_postal'];
            $ocShippingDetail->country = "";
            $ocShippingDetail->geo = $prodDet['origin_info']['destination_geo'];
            $ocShippingDetail->shipping_agency = $prodDet['order_info']['shipping_info']['logistic_name'];
            $ocShippingDetail->service_type = $prodDet['order_info']['shipping_info']['logistic_service'];
            $ocShippingDetail->accept_deadline = date("Y-m-d H:i:s", strtotime($prodDet['shipment_fulfillment']['accept_deadline']));
            $ocShippingDetail->confirm_shipping_deadline = date("Y-m-d H:i:s", strtotime($prodDet['shipment_fulfillment']['confirm_shipping_deadline']));
            $ocShippingDetail->shipping_details = json_encode($prodDet['order_info']);
            $ocShippingDetail->save();

            $ocStatus = OcStatus::where('store_id', $prodDet['seller_id'])
                ->where('order_id', $prodDet['order_id'])
                ->delete();
            $saveStatuses = [];
            foreach ($prodDet['order_info']['order_history'] as $history) {
                $saveStatuses[] = [
                    'store_id' => $prodDet['seller_id'],
                    'order_id' => $prodDet['order_id'],
                    'status_code' => $history['hist_status_code'],
                    'status_description' => $this->tokpedRepo->statusCode($history['hist_status_code']),
                    'status_type' => 'Order Status Update',
                    'status_user' => 'SYSTEM',
                    'createdAt' => date("Y-m-d H:i:s", strtotime($history['timestamp'])),
                    'updatedAt' => date("Y-m-d H:i:s", strtotime($history['timestamp']))
                ];
            }
            OcStatus::insert($saveStatuses);

            DB::commit();
            return resultFunction("", true);
        } catch (\Exception $e) {
            return resultFunction("Err code ER-SO: catch " . $e->getMessage());
        }
    }

    public function marketplaceProduct($id, $filters, $companyId) {
        try {
            $ocStore =  OcStore::find($id);
            if (!$ocStore) return resultFunction('Err ER-MD: store not found');

            if ($ocStore->company_id != $companyId) return resultFunction('Err code ER-MD: store is not belongs you');

            if (!$ocStore->store_id) return resultFunction('Err code ER-MD: store id not found');

            $result = [];
            if ($ocStore->store_type === 'Tiktok') {
                $respGet = $this->tiktokRepo->listProductByShopId($ocStore);
            } else {
                $page = isset($filters['page']) ? $filters['page'] : 1;
                $respGet = $this->tokpedRepo->getProductByShopId($ocStore->store_id, $page);
            }
            if (!$respGet['status']) return $respGet;

            return resultFunction("", true, $this->convertProduct($ocStore->store_type, $respGet['data']));
        } catch (\Exception $e) {
            return resultFunction("Err code ER-MD catch: " . $e->getMessage());
        }
    }

    public function convertProduct($type, $datas) {
        $result = [];
        foreach ($datas as $data) {
            if ($type === 'Tokopedia') {
                $result[] = [
                    "product_id" => $data['basic']['productID'],
                    "name" => $data['basic']['name'],
                    "price" => $data['price']['value'],
                    "payload" => $data
                ];
            } else if ($type === 'Tiktok') {
                $result[] = [
                    "product_id" => $data['id'],
                    "name" => $data['name'],
                    "price" => $data['skus'][0]['price']['original_price'],
                    "payload" => $data
                ];
            }
        }
        return $result;
    }

    public function getProductOnly($storeId, $companyId) {
        $products = EcomProduct::with(['ecom_product_stores', 'ecom_checkout_forms'])
            ->whereDoesntHave('ecom_product_stores', function ($q) use ($storeId) {
                $q->where('store_id', $storeId);
            })
            ->where('company_id', $companyId)
            ->get();
        return $products;
    }

    public function deleteProductOnly($id) {
        try {
            $ecomProductStore = EcomProductStore::find($id);
            if (!$ecomProductStore) return resultFunction("Err code ER-SPO: mapping not found");

            $ecomProductStore->delete();

            return resultFunction("Success delete", true);
        } catch (\Exception $e) {
            return resultFunction("Err code ER-SPO: catch " . $e->getMessage());
        }
    }

    public function setProductOnly($data, $companyId) {
        try {
            $validator = Validator::make($data, [
                'id' => 'required',
                'data' => 'required'
            ]);
            if ($validator->fails()) return resultFunction('Err code ER-SPO: validation err ' . $validator->errors());

            $company = Company::find($companyId);
            if (!$company) return resultFunction('Err code ER-SPO: company not found');

            $store = OcStore::find($data['id']);
            if (!$store) return resultFunction('Err code ER-SPO: store not found');

            $ids = [];
            foreach ($data['data'] as $item) {
                if (isset($item['is_selected'])) {
                    if ($item['is_selected']) {
                        $ids[] = $item['id'];
                    }
                }
            }

            if (count($ids) === 0)  return resultFunction('Err code ER-SPO: please select product');

            $products = EcomProduct::with([])
                ->whereIn('id', $ids)
                ->get();

            if (count($ids) !== count($products)) return resultFunction("Err code ER-SPO: the product is not match");
            $paramSave = [];
            foreach ($ids as $id) {
                $paramSave[] = [
                    'product_id' => $id,
                    'store_id' => $store->id,
                    'createdAt' => date("Y-m-d H:i:s"),
                    'updatedAt' => date("Y-m-d H:i:s")
                ];
            }

            EcomProductStore::insert($paramSave);

            return resultFunction("Success to save it", true);
        } catch (\Exception $e) {
            return resultFunction("Err code ER-SPO: catch " . $e->getMessage());
        }
    }

    public function storeMarketplaceProduct($data, $companyId) {
        try {
            DB::beginTransaction();
            $validator = Validator::make($data, [
                'store_id' => 'required',
                'data' => 'required'
            ]);
            if ($validator->fails()) return resultFunction('Err code ER-SMP: validation err ' . $validator->errors());

            $company = Company::find($companyId);
            if (!$company) return resultFunction('Err code ER-SMP: company not found');

            $store = OcStore::find($data['store_id']);
            if (!$store) return resultFunction('Err code ER-SMP: store not found');

            $ecomProductStore = EcomProductStore::where('product_id', $data['product_id'])
                ->where('store_id', $data['store_id'])->first();
            if (!$ecomProductStore) return resultFunction('Err code ER-SMP: product store not found');

            $ids = [];
            foreach ($data['data'] as $item) {
                if (isset($item['selected'])) {
                    if ($item['selected']) {
                        $ids[] = $item['data'];
                    }
                }
            }

            if (count($ids) === 0)  return resultFunction('Err code ER-SMP: please select product');

            EcomProductMarketplaceMapping::where("ecom_product_store_id", $ecomProductStore->id)
                ->delete();

            $paramSave = [];
            foreach ($ids as $id) {
                $paramSave[] = [
                    'ecom_product_store_id' => $ecomProductStore->id,
                    'marketplace_product_id' => $id['product_id'],
                    'listed_price' => $id['price'],
                    'additional_details' => json_encode($id['payload']),
                    'createdAt' => date("Y-m-d H:i:s"),
                    'updatedAt' => date("Y-m-d H:i:s")
                ];
            }

            EcomProductMarketplaceMapping::insert($paramSave);

            DB::commit();
            return resultFunction("Success to save it", true);
        } catch (\Exception $e) {
            return resultFunction("Err code ER-SMP: catch " . $e->getMessage());
        }
    }

    public function marketplaceOrderDetail($orderId, $companyId) {
        try {
            $ocOrder = OcOrder::with(['oc_order_items', 'oc_invoice', 'oc_customer', 'oc_payment_detail', 'oc_shipping_detail', 'oc_statuses'])
                ->where('order_id', $orderId)
                ->first();
            if (!$ocOrder) return resultFunction("Err code ER-MO: order data not found");;

            return resultFunction("", true, $ocOrder);
        } catch (\Exception $e) {
            return resultFunction("Err code ER-MO catch: " . $e->getMessage());
        }
    }

    public function inquirySave($data, $companyId) {
        try {
            DB::beginTransaction();
            $validator = Validator::make($data, [
                'account_number' => 'required',
                'account_bank' => 'required',
                'account_holder_name' => 'required'
            ]);
            if ($validator->fails()) return resultFunction('Err code ER-IS: validation err ' . $validator->errors());

            $company = Company::find($companyId);
            if (!$company) return resultFunction('Err code ER-IS: company not found');

            $ecomInquiry = EcomInquiry::with([])
                ->where('company_id', $company->id)
                ->first();
            if(!$ecomInquiry) {
                $ecomInquiry = new EcomInquiry();
                $ecomInquiry->company_id = $company->id;
            }
            $ecomInquiry->account_number = $data['account_number'];
            $ecomInquiry->account_bank = $data['account_bank'];
            $ecomInquiry->account_holder_name = $data['account_holder_name'];
            $ecomInquiry->save();

            DB::commit();
            return resultFunction("Success to save it", true);
        } catch (\Exception $e) {
            return resultFunction("Err code ER-IS: catch " . $e->getMessage());
        }
    }

    public function inquiryDetail($companyId) {
        try {
            $ecomInquiry = EcomInquiry::with([])
                ->where('company_id', $companyId)
                ->first();
            if (!$ecomInquiry) return resultFunction("Err code ER-ID: inquiry data not found");

            return resultFunction("", true, $ecomInquiry);
        } catch (\Exception $e) {
            return resultFunction("Err code ER-ID catch: " . $e->getMessage());
        }
    }

    public function storeMasterStatusDefault($companyId) {
        try {
            DB::beginTransaction();

            // Remove all previous statuses
            EcomMasterStatusSub::where('company_id', $companyId)->delete();
            EcomMasterFollowUp::where('company_id', $companyId)->delete();

            // Get all default master status
            $ecomMasterStatuses = EcomMasterStatus::all();

            // Map data to input variable
            $ecomMasterStatusSubs = [];
            foreach ($ecomMasterStatuses as $ecomMS) {
                $sort = 1;
                foreach ($ecomMS->sub_statuses as $subStatus) {
                    $ecomMasterStatusSubs[] = [
                        "company_id" => $companyId,
                        "ecom_master_status_id" => $ecomMS->id,
                        "title" => $subStatus,
                        "sort" => $sort,
                        "createdAt" => date("Y-m-d H:i:s"),
                        "updatedAt" => date("Y-m-d H:i:s")
                    ];
                    $sort++;
                }
            }

            // Save it to database
            // EcomMasterStatusSub::insert($ecomMasterStatusSubs);

            // Get default data from helpers
            $followUpData = defaultFollowUpEcommerce();

            // Map data to input variable
            $ecomMFInput = [];
            foreach ($followUpData as $fd) {
                $ecomMFInput[] = [
                    "company_id" => $companyId,
                    "key" => $fd['key'],
                    "value_of_key" => $fd['value'],
                    "default_text" => $fd['defaultText'],
                    "current_text" => $fd['currentText'],
                    "createdAt" => date("Y-m-d H:i:s"),
                    "updatedAt" => date("Y-m-d H:i:s")
                ];
            }

            // Save it to database
            EcomMasterFollowUp::insert($ecomMFInput);

            DB::commit();
            return resultFunction("", true);
        } catch (\Exception $e) {
            return resultFunction("Err code ER-SMSD catch: " . $e->getMessage());
        }
    }

    public function masterStatusIndex($companyId) {
        try {
            $ecomMasterStatuses = EcomMasterStatus::with(['ecom_master_status_subs' => function ($query) use ($companyId) {
                $query->where('company_id', '=', $companyId);
            }])->get();

            return resultFunction("", true, $ecomMasterStatuses);
        } catch (\Exception $e) {
            return resultFunction("Err code ER-ID catch: " . $e->getMessage());
        }
    }

    public function masterStatusChangeStatus($data, $companyId) {
        try {
            DB::beginTransaction();
            $validator = Validator::make($data, [
                'master_status_id' => 'required',
                'title' => 'required'
            ]);
            if ($validator->fails()) return resultFunction('Err code ER-MSCS: validation err ' . $validator->errors());

            $company = Company::find($companyId);
            if (!$company) return resultFunction('Err code ER-MSCS: company not found');

            $ecomMasterStatus = EcomMasterStatus::find($data['master_status_id']);
            if (!$ecomMasterStatus) return resultFunction('Err code ER-MSCS: master status not found');

            if (!isset($data['master_status_sub_id'])) {
                $ecomMasterStatusSub = new EcomMasterStatusSub();
                $ecomMasterStatusSub->company_id = $companyId;
                $ecomMasterStatusSub->ecom_master_status_id = $data['master_status_id'];
            } else {
                $ecomMasterStatusSub = EcomMasterStatusSub::find($data['master_status_sub_id']);
                if (!$ecomMasterStatus) return resultFunction('Err code ER-MSCS: master status not found');
            }

            $ecomMasterStatusSub->title = $data['title'];
            $ecomMasterStatusSub->save();

            DB::commit();
            return resultFunction("Success to save it", true);
        } catch (\Exception $e) {
            return resultFunction("Err code ER-MSCS: catch " . $e->getMessage());
        }
    }

    public function masterStatusDeleteStatus($id, $companyId) {
        try {
            $company = Company::find($companyId);
            if (!$company) return resultFunction('Err code ER-MSCS: company not found');

            $ecomMasterStatusSub = EcomMasterStatusSub::find($id);
            if (!$ecomMasterStatusSub) return resultFunction('Err code ER-MSCS: master status not found');

            if ($ecomMasterStatusSub->company_id != $companyId)  return resultFunction('Err code ER-MSCS: master status sub is not belongs to you');

            $ecomMasterStatusSub->delete();

            return resultFunction("Success to save it", true);
        } catch (\Exception $e) {
            return resultFunction("Err code ER-MSCS: catch " . $e->getMessage());
        }
    }

    public function masterFollowupIndex($companyId) {
        try {
            $ecomMasterFollowups = EcomMasterFollowUp::with([])
                ->where('company_id', $companyId)
                ->get();

            return resultFunction("", true, $ecomMasterFollowups);
        } catch (\Exception $e) {
            return resultFunction("Err code ER-MFI catch: " . $e->getMessage());
        }
    }

    public function masterFollowupUpdate($id, $data, $companyId) {
        try {
            DB::beginTransaction();
            $validator = Validator::make($data, [
                'current_text' => 'required'
            ]);
            if ($validator->fails()) return resultFunction('Err code ER-MFU: validation err ' . $validator->errors());

            $company = Company::find($companyId);
            if (!$company) return resultFunction('Err code ER-MFU: company not found');

            $masterFollowup = EcomMasterFollowUp::find($id);
            if (!$masterFollowup) return resultFunction('Err code ER-MFU: master follow up not found');
            
            if ($masterFollowup->company_id != $companyId) return resultFunction('Err code ER-MFU: master follow up is invalid');

            $masterFollowup->current_text = $data['current_text'];
            $masterFollowup->save();

            DB::commit();
            return resultFunction("Success to save it", true);
        } catch (\Exception $e) {
            return resultFunction("Err code ER-MFU: catch " . $e->getMessage());
        }
    }
}