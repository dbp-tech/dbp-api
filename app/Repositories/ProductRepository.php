<?php

namespace App\Repositories;

use App\Models\CheckoutForm;
use App\Models\Company;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductEntityType;
use App\Models\ProductFuTemplate;
use App\Models\ProductTypeMapping;
use App\Models\Recipe;
use App\Models\Variant;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ProductRepository
{
    public function index($filters)
    {
        $product = Product::with(['product_type_mapping_variants.variant', 'product_type_mapping_recipes.recipe', 'product_fu_templates', 'product_category']);
        if (!empty($filters['company_id'])) {
            $product = $product->where('company_id', $filters['company_id']);
        }
        $product = $product->orderBy('id', 'desc')->paginate(25);
        return $product;
    }

    public function save($data)
    {
        try {
            DB::beginTransaction();
            $validator = Validator::make($data, [
                'company_id' => 'required',
                'title' => 'required',
                'price' => 'required',
                'image' => 'required',
            ]);
            if ($validator->fails()) return resultFunction('Err code PR-S: validation err ' . $validator->errors());

            $company = Company::find($data['company_id']);
            if (!$company) return resultFunction('Err code PR-S: company not found');

            $productCategory = ProductCategory::find($data['category_id']);
            if (!$productCategory) return resultFunction('Err code PR-S: category not found');

            if (isset($data['id'])) {
                $product = Product::find($data['id']);
                if (!$product) return resultFunction('Err code PR-S: product not found');
            } else {
                $product = new Product();
            }
            $product->company_id = $data['company_id'];
            $product->category_id = $data['category_id'];
            $product->title = $data['title'];
            $product->description = $data['description'];
            $product->price = $data['price'];
            $product->image = json_encode($data['image']);
            $product->save();

            $ptmParams = [];
            foreach ($data['details'] as $detail) {
                $productEntityType = ProductEntityType::find($detail['entity_id']);
                if (!$productEntityType) return resultFunction("Err code PR-S: product entity type not found");

                if ($productEntityType->product_type_name === 'variant') {
                    $dataOri = Variant::with([])
                        ->whereIn('id', $detail['entity_data'])
                        ->get();
                } else {
                    $dataOri = Recipe::with([])
                        ->whereIn('id', $detail['entity_data'])
                        ->get();
                }
                if (count($dataOri) !== count($detail['entity_data'])) return resultFunction("Err code PR-S: the data is not match");
                foreach ($detail['entity_data'] as $item) {
                    $ptmParams[] = [
                        "product_id" => $product->id,
                        "entity_type" => $productEntityType->product_type_name,
                        "entity_id" => $item,
                        "createdAt" => date("Y-m-d H:i:s"),
                        "updatedAt" => date("Y-m-d H:i:s")
                    ];
                }
            }

            if (isset($data['id'])) {
                ProductTypeMapping::where('product_id', $product->id)->delete();
                CheckoutForm::where('product_id', $product->id)->delete();
            }

            ProductTypeMapping::insert($ptmParams);
            CheckoutForm::insert($this->checkoutFormTemplate($product));
            if (!isset($data['id'])) {
                ProductFuTemplate::insert($this->fuTemplate($product));
            }

            DB::commit();
            return resultFunction("Success to create product", true);
        } catch (\Exception $e) {
            DB::rollBack();
            return resultFunction("Err code PR-S catch: " . $e->getMessage());
        }
    }

    public function delete($id) {
        try {
            DB::beginTransaction();
            $product =  Product::find($id);
            if (!$product) return resultFunction('Err PR-D: product category not found');
            $product->delete();

            ProductTypeMapping::where('product_id', $id)->delete();
            CheckoutForm::where('product_id', $id)->delete();

            DB::commit();
            return resultFunction("Success to delete product", true);
        } catch (\Exception $e) {
            return resultFunction("Err code PR-D catch: " . $e->getMessage());
        }
    }

    public function detail($id) {
        try {
            DB::beginTransaction();
            $product =  Product::with(['product_type_mapping_variants.variant', 'product_type_mapping_recipes.recipe',
                'product_fu_templates', 'checkout_forms.orders'])
                ->find($id);
            if (!$product) return resultFunction('Err PR-D: product category not found');
            DB::commit();
            return resultFunction("", true, $product);
        } catch (\Exception $e) {
            return resultFunction("Err code PR-D catch: " . $e->getMessage());
        }
    }

    public function checkoutFormTemplate($product) {
        return [
            "product_id" => $product->id,
            "title" => "default checkout form",
            "template" => '{
                    "type": "right sidebar",
                    "background_color": "blue"
                }',
            "header" => '{
                    "image": {
                        "url": "",
                        "path": ""
                    },
                    "title": {
                        "is_show": true,
                        "message": ""
                    },
                    "tagline": {
                        "is_show": true,
                        "message": ""
                    }
                }',
            "show_product_image" => true,
            "guarantee_seals" => '{
                    "is_show": true,
                    "message": "100% jaminan kepuasan"
                }',
            "requested_fields" => '[
                    {
                        "key": "name",
                        "field": "input",
                        "type": "text",
                        "is_required": true,
                        "label": "Name",
                        "placeholder": "name here"
                    },
                    {
                        "key": "phone",
                        "field": "input",
                        "type": "text",
                        "is_required": true,
                        "label": "Phone",
                        "placeholder": "phone / wa here"
                    },
                    {
                        "key": "email",
                        "field": "input",
                        "type": "text",
                        "is_required": false,
                        "label": "Email",
                        "placeholder": "email here"
                    },
                    {
                        "key": "sub_district",
                        "field": "input",
                        "type": "text",
                        "is_required": false,
                        "label": "Sub District",
                        "placeholder": "sub district here"
                    },
                    {
                        "key": "address",
                        "field": "textarea",
                        "is_required": false,
                        "label": "Full Address",
                        "placeholder": "address here"
                    }
                ]',
            "is_dropship" => false,
            "buy_button" => '{
                    "label": "Gabung Sekarang",
                    "color": "red"
                }',
            "video" => "",
            "content" => "ini content",
            "coupon_field" => true,
            "bullet_points" => '[
                    "point1",
                    "point2"
                ]',
            "is_order_summary" => false,
            "testimonials" => '[
                    {
                        "image": {"url": "", "path": ""},
                        "name": "",
                        "description": ""
                    }
                ]',
            "tracking" => '[
                    {
                        "type": "facebook-pixel",
                        "id": 1231231,
                        "data": ["ViewContent", "AddToCart"]
                    },
                    {
                        "type": "google-tag-manager",
                        "data": ["GTM-123", "GTM-124"]
                    }
                ]',
            "headline_text" => '{
                    "is_show_title": false,
                    "title": "",
                    "headeline": "Terimakasih sudah melakukan order {{product_name}}"
                }',
            "success_video" => "",
            "bank_accounts" => '[
                    {
                        "bank": "BCA",
                        "account_number": 123123,
                        "account_holder_name": "Budi"
                    }
                ]',
            "createdAt" => date("Y-m-d H:i:s"),
            "updatedAt" => date("Y-m-d H:i:s")
        ];
    }

    public function fuTemplate($product) {
        return [
            [
                'product_id' => $product->id,
                'title' => 'Welcome',
                "description" => '<p>Halo imam&#55357;&#56842;  Terima kasih sudah berpartisipasi di survey minat resep Chef Clara &#55357;&#56842;  Sudah siap untuk belajar resep baru dari Chef Clara? Ditunggu yaa imam</p>

                    <p> imam juga bisa mengikuti kelas resep Chef Clara yang sudah READY di kelas Chef Clara, untuk melihat daftar kelasnya silahkan klik link https://chefclara.id/kelas/</p>
                    
                    <p> Terima kasih  Chef Clara&#55357;&#56842;</p>',
                "createdAt" => date("Y-m-d H:i:s"),
                "updatedAt" => date("Y-m-d H:i:s")
            ],
            [
                'product_id' => $product->id,
                'title' => 'FU1',
                "description" => '<p>Halo kak imam&#55357;&#56842;  Terima kasih telah mengikuti kelas online bersama Chef Clara, ada kabar baik nih kak, kelasnya sudah bisa diakses dari sekarang ya kak &#55357;&#56842;</p>

                        <p> *Berikut langkah akses kelasnya :*  1) Masuk ke platform https://chefclara.id/dashboard/enrolled-courses/  2) lalu login menggunakan :</p>
                        
                        <p> Email :  Password : chef2022!  Untuk password jangan lupa menggunakan tanda seru (!)  Klik *Log In*</p>
                        
                        <p> 3) Pilih kelas yang di order  4) Klik *Start Learning*</p>
                        
                        <p> Jika ada yang ingin ditanyakan, bisa chat ke WA ini ya</p>
                        
                        <p> Terima kasih  Chef Clara&#55357;&#56842;</p>',
                "createdAt" => date("Y-m-d H:i:s"),
                "updatedAt" => date("Y-m-d H:i:s")
            ],
            [
                'product_id' => $product->id,
                'title' => 'FU2',
                "description" => '<p>Selamat siang imam... Pesanan vote resep chef clara sudah siap kirim ya... &#9786;&#55357;&#56911;</p>',
                "createdAt" => date("Y-m-d H:i:s"),
                "updatedAt" => date("Y-m-d H:i:s")
            ],
            [
                'product_id' => $product->id,
                'title' => 'FU3',
                "description" => '<p>Selamat siang, promo untuk pembelian vote resep chef clara HARI INI  Diskon Rp10.000 ya.. &#9786;&#55357;&#56911;</p>',
                "createdAt" => date("Y-m-d H:i:s"),
                "updatedAt" => date("Y-m-d H:i:s")
            ],
            [
                'product_id' => $product->id,
                'title' => 'FU4',
                "description" => '<p>Selamat siang imam,  Produk vote resep chef clara laris manis nih, stok kami sisa 5 item saja ya...  Jangan sampe kehabisan &#9786;&#55357;&#56911;</p>',
                "createdAt" => date("Y-m-d H:i:s"),
                "updatedAt" => date("Y-m-d H:i:s")
            ],
        ];
    }

    public function indexFuTemplate($filters)
    {
        $productFuTemplate = ProductFuTemplate::with([]);
        if (!empty($filters['product_id'])) {
            $productFuTemplate = $productFuTemplate->where('product_id', $filters['product_id']);
        }
        $productFuTemplate = $productFuTemplate->orderBy('id', 'desc')->paginate(25);
        return $productFuTemplate;
    }

    public function detailFuTemplate($id) {
        try {
            DB::beginTransaction();
            $productFuTemplate =  ProductFuTemplate::find($id);
            if (!$productFuTemplate) return resultFunction('Err PR-D: product fu template not found');

            DB::commit();
            return resultFunction("", true, $productFuTemplate);
        } catch (\Exception $e) {
            return resultFunction("Err code PR-De catch: " . $e->getMessage());
        }
    }

    public function saveFuTemplate($data)
    {
        try {
            DB::beginTransaction();
            $validator = Validator::make($data, [
                'id' => 'required',
                'title' => 'required',
                'description' => 'required',
            ]);
            if ($validator->fails()) return resultFunction('Err code PR-SFT: validation err ' . $validator->errors());

            $productFuTemplate = ProductFuTemplate::find($data['id']);
            if (!$productFuTemplate) return resultFunction('Err code PR-SFT:product fu template not found');

            $productFuTemplate->title = $data['title'];
            $productFuTemplate->description = $data['description'];
            $productFuTemplate->save();

            DB::commit();
            return resultFunction("Success to update product fu template", true, $productFuTemplate);
        } catch (\Exception $e) {
            DB::rollBack();
            return resultFunction("Err code PR-SFT catch: " . $e->getMessage());
        }
    }
}