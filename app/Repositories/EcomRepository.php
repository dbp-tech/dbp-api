<?php

namespace App\Repositories;

use App\Models\Company;
use App\Models\EcomProduct;
use App\Models\EcomProductCategory;
use App\Models\EcomProductCategoryMapping;
use App\Models\EcomProductMarketplaceMapping;
use App\Models\EcomProductVariant;
use App\Models\OcStore;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class EcomRepository
{
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
        $ecomProduct = EcomProduct::with(['product_variants', 'product_category.ecom_product_category']);
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
        $ocStores = OcStore::with(['ecom_products_mapping.product']);
        $ocStores = $ocStores->where('company_id', $companyId);
        $ocStores = $ocStores->orderBy('id', 'desc')->get();
        return $ocStores;
    }

    public function storeSave($data, $companyId)
    {
        try {
            $validator = Validator::make($data, [
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
            $ocStore->store_name = $data['store_name'];
            $ocStore->is_active = $data['is_active'];
            $ocStore->type_logo = $data['type_logo'];
            $ocStore->store_type = $data['store_type'];
            if (isset($data['attribute_details'])) {
                $ocStore->attribute_details = json_encode($data['attribute_details']);
            }
            $ocStore->save();

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

    public function getProductOnly($storeId, $companyId) {
        $products = EcomProduct::with(['ecom_product_marketplace_mapping'])
            ->whereDoesntHave('ecom_product_marketplace_mapping', function ($q) use ($storeId) {
                $q->where('store_id', $storeId);
            })
            ->where('company_id', $companyId)
            ->get();
        return $products;
    }

    public function deleteProductOnly($id) {
        try {
            $ecomProductMapping = EcomProductMarketplaceMapping::find($id);
            if (!$ecomProductMapping) return resultFunction("Err code ER-SPO: mapping not found");

            $ecomProductMapping->delete();

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

            EcomProductMarketplaceMapping::insert($paramSave);

            return resultFunction("Success to save it", true);
        } catch (\Exception $e) {
            return resultFunction("Err code ER-SPO: catch " . $e->getMessage());
        }
    }
}