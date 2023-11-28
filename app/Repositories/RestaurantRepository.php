<?php

namespace App\Repositories;

use App\Models\Company;
use App\Models\Recipe;
use App\Models\RsAddonsCategory;
use App\Models\RsAddonsCategoryMenu;
use App\Models\RsCategory;
use App\Models\RsCoupon;
use App\Models\RsMenu;
use App\Models\RsMenuAddon;
use App\Models\RsMenuAddonPrice;
use App\Models\RsMenuAddonRecipe;
use App\Models\RsMenuPrice;
use App\Models\RsMenuRecipe;
use App\Models\RsOrder;
use App\Models\RsOrderMenu;
use App\Models\RsOrderMenuAddon;
use App\Models\RsOutlet;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class RestaurantRepository
{
    public function indexCategory($filters, $companyId)
    {
        $rsCategories = RsCategory::with([]);
        if (!empty($filters['title'])) {
            $rsCategories = $rsCategories->where('title', 'LIKE', '%' . $filters['title'] . '%');
        }
        if (!empty($filters['description'])) {
            $rsCategories = $rsCategories->where('description', 'LIKE', '%' . $filters['description'] . '%');
        }
        $rsCategories = $rsCategories->where('company_id', $companyId);
        $rsCategories = $rsCategories->orderBy('id', 'desc')->get();
        return $rsCategories;
    }

    public function saveCategory($data, $companyId)
    {
        try {
            $validator = Validator::make($data, [
                'title' => 'required',
                'description' => 'required'
            ]);
            if ($validator->fails()) return resultFunction('Err code RR-SC: validation err ' . $validator->errors());

            $company = Company::find($companyId);
            if (!$company) return resultFunction('Err code RR-SC: company not found');

            if ($data['id']) {
                $rsCategory = RsCategory::find($data['id']);
                if (!$rsCategory) return resultFunction("Err code RR-SC: category not found");
            }  else {
                $rsCategory = new RsCategory();
            }
            $rsCategory->company_id = $company->id;
            $rsCategory->title = $data['title'];
            $rsCategory->description = $data['description'];
            $rsCategory->save();

            return resultFunction("Success to create category", true, $rsCategory);
        } catch (\Exception $e) {
            return resultFunction("Err code RR-SC catch: " . $e->getMessage());
        }
    }

    public function deleteCategory($id, $companyId) {
        try {
            $rsCategory =  RsCategory::find($id);
            if (!$rsCategory) return resultFunction('Err RR-D: restaurant category not found');

            if ($rsCategory->company_id != $companyId) return resultFunction('Err RR-D: category not found');
            $rsCategory->delete();

            return resultFunction("Success to delete restaurant category", true);
        } catch (\Exception $e) {
            return resultFunction("Err code RR-D catch: " . $e->getMessage());
        }
    }

    public function detailCategory($id, $companyId) {
        try {
            $rsCategory =  RsCategory::find($id);
            if (!$rsCategory) return resultFunction('Err RR-D: restaurant category not found');

            if ($rsCategory->company_id != $companyId) return resultFunction('Err RR-D: category not found');

            return resultFunction("Success to delete restaurant category", true, $rsCategory);
        } catch (\Exception $e) {
            return resultFunction("Err code RR-D catch: " . $e->getMessage());
        }
    }

    public function indexMenu($filters, $companyId)
    {
        $rsMenu = RsMenu::with(['rs_category', 'rs_menu_recipes.recipe', 'rs_addons_category_menus.rs_addons_category.rs_menu_addons', 'rs_menu_prices']);
        if (!empty($filters['title'])) {
            $rsMenu = $rsMenu->where('title', 'LIKE', '%' . $filters['title'] . '%');
        }
        $rsMenu = $rsMenu->where('company_id', $companyId);
        $rsMenu = $rsMenu->orderBy('id', 'desc')->get();
        return $rsMenu;
    }

    public function indexRecipe($filters, $companyId)
    {
        $rsMenu = Recipe::with([]);
        $rsMenu = $rsMenu->where('company_id', $companyId);
        $rsMenu = $rsMenu->orderBy('id', 'desc')->get();
        return $rsMenu;
    }

    public function saveMenu($data, $companyId)
    {
        try {
            $validator = Validator::make($data, [
                'category' => 'required',
                'title' => 'required',
                'price' => 'required',
                'recipes' => 'required',
                'price_onlines' => 'required',
            ]);
            if ($validator->fails()) return resultFunction('Err code RR-SM: validation err ' . $validator->errors());
            DB::beginTransaction();

            $company = Company::find($companyId);
            if (!$company) return resultFunction('Err code RR-SM: company not found');

            $rsCategory = RsCategory::find($data['category']['value']);
            if (!$rsCategory) return resultFunction('Err code RR-SM: company not found');

            if (count($data['price_onlines']) == 0) return resultFunction('Err code RR-SM: price online not found');

            if ($data['id']) {
                $rsMenu = RsMenu::find($data['id']);
                if (!$rsMenu) return resultFunction("Err code RR-SM: menu not found");
                RsMenuRecipe::where('rs_menu_id', $data['id'])->delete();
                RsMenuPrice::where('rs_menu_id', $data['id'])->delete();
                RsAddonsCategoryMenu::where('rs_menu_id', $rsMenu->id)->delete();
            } else {
                $rsMenu = new RsMenu();
            }
            $rsMenu->company_id = $company->id;
            $rsMenu->rs_category_id = $rsCategory->id;
            $rsMenu->title = $data['title'];
            $rsMenu->image = isset($data['image']) ? ($data['image'] ? : '') : '';
            $rsMenu->price = $data['price'];
            $rsMenu->save();

            $rsMenuRecipes = [];
            foreach ($data['recipes'] as $recip) {
                $rsMenuRecipes[] = [
                    'rs_menu_id' => $rsMenu->id,
                    'recipe_id' => $recip['value'],
                    'createdAt' => date("Y-m-d H:i:s"),
                    'updatedAt' => date("Y-m-d H:i:s")
                ];
            }
            RsMenuRecipe::insert($rsMenuRecipes);


            $rsMenuPrices = [];
            foreach ($data['price_onlines'] as $price) {
                $rsMenuPrices[] = [
                    'rs_menu_id' => $rsMenu->id,
                    'title' => $price['title'],
                    'price' => $price['price'],
                    'createdAt' => date("Y-m-d H:i:s"),
                    'updatedAt' => date("Y-m-d H:i:s")
                ];
            }
            RsMenuPrice::insert($rsMenuPrices);

            if (isset($data['addons_categories'])) {
                if (count($data['addons_categories']) > 0) {
                    $rsAddonsCategoryMenus = [];
                    foreach ($data['addons_categories'] as $addonsCategory) {
                        $rsAddonsCategoryMenus[] = [
                            'rs_addons_category_id' => $addonsCategory['id'],
                            'rs_menu_id' => $rsMenu->id,
                            'createdAt' => date("Y-m-d H:i:s"),
                            'updatedAt' => date("Y-m-d H:i:s")
                        ];
                    }
                    RsAddonsCategoryMenu::insert($rsAddonsCategoryMenus);
                }
            }

            DB::commit();
            return resultFunction("Success to create menu", true, $rsMenu);
        } catch (\Exception $e) {
            return resultFunction("Err code RR-SM catch: " . $e->getMessage());
        }
    }

    public function deleteMenu($id, $companyId) {
        try {
            $rsMenu =  RsMenu::find($id);
            if (!$rsMenu) return resultFunction('Err RR-D: menu not found');

            if ($rsMenu->company_id != $companyId) return resultFunction('Err RR-D: menu not found');
            $rsMenu->delete();
            RsMenuRecipe::where('rs_menu_id', $id)->delete();

            return resultFunction("Success to delete menu", true);
        } catch (\Exception $e) {
            return resultFunction("Err code RR-D catch: " . $e->getMessage());
        }
    }

    public function detailMenu($id, $companyId) {
        try {
            $rsMenu =  RsMenu::with(['rs_category', 'rs_menu_recipes.recipt', 'rs_menu_prices'])->find($id);
            if (!$rsMenu) return resultFunction('Err RR-D: menu not found');

            if ($rsMenu->company_id != $companyId) return resultFunction('Err RR-D: menu not found');

            return resultFunction("Success to delete menu", true, $rsMenu);
        } catch (\Exception $e) {
            return resultFunction("Err code RR-D catch: " . $e->getMessage());
        }
    }

    public function indexOutlet($filters, $companyId)
    {
        $rsOutlets = RsOutlet::with([]);
        $rsOutlets = $rsOutlets->where('company_id', $companyId);
        $rsOutlets = $rsOutlets->orderBy('id', 'desc')->get();
        return $rsOutlets;
    }

    public function saveOutlet($data, $companyId)
    {
        try {
            $validator = Validator::make($data, [
                'name' => 'required',
                'image' => 'required',
                'address' => 'required',
            ]);
            if ($validator->fails()) return resultFunction('Err code RR-SO: validation err ' . $validator->errors());

            $company = Company::find($companyId);
            if (!$company) return resultFunction('Err code RR-SO: company not found');

            if ($data['id']) {
                $rsOutlet = RsOutlet::find($data['id']);
                if (!$rsOutlet) return resultFunction('Err code RR-SO: outlet not found');
            } else {
                $rsOutlet = new RsOutlet();
            }
            $rsOutlet->company_id = $company->id;
            $rsOutlet->name = $data['name'];
            $rsOutlet->image = $data['image'];
            $rsOutlet->address = $data['address'];
            $rsOutlet->save();

            return resultFunction("Success to create outlet", true, $rsOutlet);
        } catch (\Exception $e) {
            return resultFunction("Err code RR-SO catch: " . $e->getMessage());
        }
    }

    public function deleteOutlet($id, $companyId) {
        try {
            $rsOutlet = RsOutlet::find($id);
            if (!$rsOutlet) return resultFunction('Err RR-D: outlet not found');

            if ($rsOutlet->company_id != $companyId) return resultFunction('Err RR-D: outlet not found');
            $rsOutlet->delete();

            return resultFunction("Success to delete menu", true);
        } catch (\Exception $e) {
            return resultFunction("Err code RR-D catch: " . $e->getMessage());
        }
    }

    public function saveOrder($data, $companyId)
    {
        try {
            DB::beginTransaction();
            $validator = Validator::make($data, [
                'menu_data' => 'required',
                'table_number' => 'required',
                'order_type' => 'required',
                'name' => 'required',
                'payment_type' => 'required',
            ]);
            if ($validator->fails()) return resultFunction('Err code RR-SOr: validation err ' . $validator->errors());

            $company = Company::find($companyId);
            if (!$company) return resultFunction('Err code RR-SOr: company not found');

            $rsOutlet = RsOutlet::find($data['outlet_id']);
            if (!$rsOutlet) return resultFunction('Err code RR-SOr: outlet not found');

            if (count($data['menu_data']) == 0) return resultFunction("Err code RR-SOr: menu data is not found");

            $rsMenus = RsMenu::with(['rs_menu_prices'])
                ->whereIn('id', array_column($data['menu_data'], 'menu_id'))
                ->get();

            $isEdit = false;
            if (isset($data['id'])) {
                $rsOrder = RsOrder::find($data['id']);
                if (!$rsOrder) return resultFunction('Err code RR-SOr: order not found');
                $isEdit = true;

                RsOrderMenu::where('rs_order_id', $data['id'])->delete();
            } else {
                $rsOrder = new RsOrder();
            }
            $rsOrder->company_id = $company->id;
            $rsOrder->rs_outlet_id = $rsOutlet->id;
            $rsOrder->order_number = "";
            $rsOrder->table_number = $data['table_number'];
            $rsOrder->order_type = $data['order_type'];
            $rsOrder->price_total = 0;
            $rsOrder->name = $data['name'];
            if (isset($data['phone'])) {
                if ($data['phone']) {
                    $rsOrder->phone = $data['phone'];
                }
            }
            $rsOrder->payment_type = $data['payment_type'];
            $rsOrder->last_status = 'requested';
            $rsOrder->save();

            if (!$isEdit) {
                $rsOrder->order_number = "ORDER" . $rsOrder->id;
                $rsOrder->save();
            }

            $couponDbs = RsCoupon::with(['rs_coupon_menus'])
                ->whereIn('id', array_column($data['menu_data'], 'coupon_id'))
                ->get();

            $totalPrice = 0;
            $totalDiscountPrice = 0;
            $totalAddonPrice = 0;
            $rsOrderMenuAddon = [];
            foreach ($data['menu_data'] as $menu) {
                $dataMenu = $rsMenus->where('id', $menu['menu_id'])->first();
                $discountPrice = 0;
                $couponData = $couponDbs->where('id', $menu['coupon_id'])->first();
                if ($menu['coupon_id'] AND !$couponData) return resultFunction("Err code RR-SOr: the coupon data is not found");

                $price = $dataMenu->price;
                if (in_array($data['order_type'], ['gofood', 'shopeefood'])) {
                    foreach ($dataMenu->rs_menu_prices as $rsMenuPrice) {
                        if ($rsMenuPrice->title == $data['order_type']) {
                            $price = $rsMenuPrice->price;
                            break;
                        }
                    }
                }

                $totalPriceQty = $price * $menu['quantity'];
                if ($couponData) {
                    if ($couponData->coupon_type === 'percentage') {
                        $discountPrice = $couponData->type_value * $totalPriceQty / 100;
                    } else {
                        $discountPrice = $couponData->type_value;
                    }
                }


                $rsOrderMenu = RsOrderMenu::create([
                    "rs_order_id" => $rsOrder->id,
                    "rs_menu_id" => $dataMenu->id,
                    "quantity" => $menu['quantity'],
                    "menu_title" => $dataMenu->title,
                    "menu_price" => $totalPriceQty,
                    "menu_image" => $dataMenu->image,
                    "discount_price" => $discountPrice,
                    "total_price" => $totalPriceQty - $discountPrice,
                    "coupon_name" => $couponData ? $couponData->coupon_name : null,
                    "coupon_type" => $couponData ? $couponData->coupon_type : null,
                    "coupon_value" => $couponData ? $couponData->type_value : 0
                ]);

                $addonPrice = 0;
                if (count($menu['addons']) > 0) {
                    $rsMenuAddons = RsMenuAddon::with(['rs_menu_addon_prices'])->whereIn('id', array_column($menu['addons'], 'id'))->get();
                    foreach ($menu['addons'] as $addon) {
                        $menuAddonSelected = $rsMenuAddons->where('id', $addon['id'])->first();
                        if (!$menuAddonSelected) return resultFunction("Err code RR-SOr: menu addon not found");

                        $price = $menuAddonSelected->price;
                        if (in_array($data['order_type'], ['gofood', 'shopeefood'])) {
                            foreach ($menuAddonSelected->rs_menu_addon_prices as $rsMenuAddonPrice) {
                                if ($rsMenuAddonPrice->title == $data['order_type']) {
                                    $price = $rsMenuAddonPrice->price;
                                    break;
                                }
                            }
                        }

                        $rsOrderMenuAddon[] = [
                            "rs_order_menu_id" => $rsOrderMenu->id,
                            "rs_menu_addon_id" => $menuAddonSelected->id,
                            "quantity" => $addon['qty'],
                            "addon_title" => $menuAddonSelected->title,
                            "addon_price" => $price,
                            "addon_image" => $dataMenu->image,
                            "createdAt" => date("Y-m-d H:i:s"),
                            "updatedAt" => date("Y-m-d H:i:s")
                        ];
                        $addonPrice = $addonPrice + ($addon['qty'] * $price);
                    }
                }
                if ($addonPrice > 0) {
                    $rsOrderMenu->addon_price = $addonPrice;
                    $rsOrderMenu->save();
                }

                $totalPrice = $totalPrice + $totalPriceQty;
                $totalDiscountPrice = $totalDiscountPrice + $discountPrice;
                $totalAddonPrice = $totalAddonPrice + $addonPrice;
            }
            RsOrderMenuAddon::insert($rsOrderMenuAddon);
            $rsOrder->price_total = $totalPrice;
            $rsOrder->discount_price_total = $totalDiscountPrice;
            $rsOrder->addon_price = $totalAddonPrice;
            $rsOrder->price_total_final = $totalPrice - $totalDiscountPrice + $totalAddonPrice;
            $rsOrder->save();

            DB::commit();
            return resultFunction("Success to create order", true, $rsOrder);
        } catch (\Exception $e) {
            return resultFunction("Err code RR-SOr catch: " . $e->getMessage());
        }
    }

    public function indexOrder($filters, $companyId, $all = false)
    {
        $rsOrders = RsOrder::with(['rs_order_menus.rs_menu', 'rs_outlet', 'rs_order_menus.rs_order_menu_addons']);
        $rsOrders = $rsOrders->where('company_id', $companyId);


        if (!empty($filters['order_number'])) {
            $rsOrders = $rsOrders->where('order_number', 'like', '%' . $filters['order_number'] . '%');
        }

        if (!empty($filters['table_number'])) {
            $rsOrders = $rsOrders->where('table_number', 'like', '%' . $filters['table_number'] . '%');
        }

        if (!empty($filters['order_type'])) {
            $rsOrders = $rsOrders->where('order_type', $filters['order_type']);
        }

        if (!empty($filters['name'])) {
            $rsOrders = $rsOrders->where('name', 'like', '%' . $filters['name'] . '%');
        }

        if (!empty($filters['payment_type'])) {
            $rsOrders = $rsOrders->where('payment_type', 'like', '%' . $filters['payment_type'] . '%');
        }

        if (!empty($filters['created_at'])) {
            $rsOrders = $rsOrders->whereBetween('createdAt', [$filters['created_at'] . ' 00:00:00', $filters['created_at'] . ' 23:59:59']);
        }
        if (!$all) {
            $rsOrders = $rsOrders->orderBy('id', 'desc')->paginate(5);
        } else {
            $rsOrders = $rsOrders->orderBy('id', 'desc')->get();
        }
        return $rsOrders;
    }

    public function indexMenuAddons($filters, $companyId)
    {
        $rsMenu = RsMenuAddon::with(['rs_addons_category', 'rs_menu_addon_recipes.recipe', 'rs_menu_addon_prices']);
        if (!empty($filters['title'])) {
            $rsMenu = $rsMenu->where('title', 'LIKE', '%' . $filters['title'] . '%');
        }
        if (!empty($filters['rs_menu_id'])) {
            $rsMenu = $rsMenu->where('rs_menu_id',  $filters['rs_menu_id']);
        }
        $rsMenu = $rsMenu->where('company_id', $companyId);
        $rsMenu = $rsMenu->orderBy('id', 'desc')->get();
        return $rsMenu;
    }

    public function saveMenuAddons($data, $companyId)
    {
        try {
            $validator = Validator::make($data, [
                'addons_category' => 'required',
                'title' => 'required',
                'price' => 'required',
                "price_onlines" => 'required'
            ]);
            if ($validator->fails()) return resultFunction('Err code RR-SM: validation err ' . $validator->errors());
            DB::beginTransaction();

            $company = Company::find($companyId);
            if (!$company) return resultFunction('Err code RR-SM: company not found');

            $rsAddonsCategory = RsAddonsCategory::find($data['addons_category']['value']);
            if (!$rsAddonsCategory) return resultFunction('Err code RR-SM: menu not found');

            if ($data['id']) {
                $rsMenuAddon = RsMenuAddon::find($data['id']);
                if (!$rsMenuAddon) return resultFunction("Err code RR-SM: menu addon not found");
                RsMenuAddonRecipe::where('rs_menu_addon_id', $data['id'])->delete();
                RsMenuAddonPrice::where('rs_menu_addon_id', $data['id'])->delete();
            } else {
                $rsMenuAddon = new RsMenuAddon();
            }
            $rsMenuAddon->company_id = $company->id;
            $rsMenuAddon->rs_addons_category_id = $rsAddonsCategory->id;
            $rsMenuAddon->title = $data['title'];
            $rsMenuAddon->image = isset($data['image']) ? ($data['image'] ? : '') : '';
            $rsMenuAddon->price = $data['price'];
            $rsMenuAddon->save();

            if (isset($data['recipes'])) {
                $rsMenuAddonRecipes = [];
                foreach ($data['recipes'] as $recip) {
                    $rsMenuAddonRecipes[] = [
                        'rs_menu_addon_id' => $rsMenuAddon->id,
                        'recipe_id' => $recip['value'],
                        'createdAt' => date("Y-m-d H:i:s"),
                        'updatedAt' => date("Y-m-d H:i:s")
                    ];
                }
                RsMenuAddonRecipe::insert($rsMenuAddonRecipes);
            }
            $rsMenuAddonPrices = [];
            foreach ($data['price_onlines'] as $priceOl) {
                $rsMenuAddonPrices[] = [
                    'rs_menu_addon_id' => $rsMenuAddon->id,
                    'title' => $priceOl['title'],
                    'price' => $priceOl['price'],
                    'createdAt' => date("Y-m-d H:i:s"),
                    'updatedAt' => date("Y-m-d H:i:s")
                ];
            }
            RsMenuAddonPrice::insert($rsMenuAddonPrices);

            DB::commit();
            return resultFunction("Success to create menu addons", true, $rsMenuAddon);
        } catch (\Exception $e) {
            return resultFunction("Err code RR-SM catch: " . $e->getMessage());
        }
    }

    public function deleteMenuAddons($id, $companyId) {
        try {
            $rsMenuAddon =  RsMenuAddon::find($id);
            if (!$rsMenuAddon) return resultFunction('Err RR-D: menu addon not found');

            if ($rsMenuAddon->company_id != $companyId) return resultFunction('Err RR-D: menu addon not found');
            $rsMenuAddon->delete();
            RsMenuAddonRecipe::where('rs_menu_addon_id', $id)->delete();

            return resultFunction("Success to delete menu addon", true);
        } catch (\Exception $e) {
            return resultFunction("Err code RR-D catch: " . $e->getMessage());
        }
    }

    public function deleteAddonsCategory($id, $companyId) {
        try {
            $rsAddonCategory =  RsAddonsCategory::find($id);
            if (!$rsAddonCategory) return resultFunction('Err RR-D: addon category not found');

            if ($rsAddonCategory->company_id != $companyId) return resultFunction('Err RR-D: addon category not found');
            $rsAddonCategory->delete();

            return resultFunction("Success to delete addon category", true);
        } catch (\Exception $e) {
            return resultFunction("Err code RR-D catch: " . $e->getMessage());
        }
    }

    public function indexAddonsCategory($filters, $companyId)
    {
        $rsAddonCategories = RsAddonsCategory::with(['rs_menu_addons_default', 'rs_menu_addons']);
        $rsAddonCategories = $rsAddonCategories->where('company_id', $companyId);
        $rsAddonCategories = $rsAddonCategories->orderBy('id', 'desc')->get();
        return $rsAddonCategories;
    }

    public function saveAddonsCategory($data, $companyId)
    {
        try {
            $validator = Validator::make($data, [
                'title' => 'required',
                'description' => 'required',
                'is_required' => 'required',
                'allow_multiple' => 'required'
            ]);
            if ($validator->fails()) return resultFunction('Err code RR-SC: validation err ' . $validator->errors());

            $company = Company::find($companyId);
            if (!$company) return resultFunction('Err code RR-SC: company not found');

            if ($data['id']) {
                $rsAddonsCategory = RsAddonsCategory::find($data['id']);
                if (!$rsAddonsCategory) return resultFunction("Err code RR-SC: addons category not found");
            }  else {
                $rsAddonsCategory = new RsAddonsCategory();
            }
            $rsAddonsCategory->company_id = $company->id;
            $rsAddonsCategory->title = $data['title'];
            $rsAddonsCategory->description = $data['description'];
            $rsAddonsCategory->is_required = $data['is_required']['value'];
            $rsAddonsCategory->allow_multiple = $data['allow_multiple']['value'];
            if (isset($data['default_value'])) {
                $rsAddonsCategory->default_value = $data['default_value']['value'];
            }
            $rsAddonsCategory->save();

            return resultFunction("Success to create addons category", true, $rsAddonsCategory);
        } catch (\Exception $e) {
            return resultFunction("Err code RR-SC catch: " . $e->getMessage());
        }
    }

    public function countOrder($data, $companyId)
    {
        $startDate = "";

        if ($data['type'] === 'custom') {
            $validator = Validator::make($data, [
                'start_date' => 'required',
                'end_date' => 'required',
            ]);
            if ($validator->fails()) return resultFunction('Err code RR-CO: validation err ' . $validator->errors());
            $startDate = $data['start_date'];
            $endDate = $data['end_date'];
        } else {
            $endDate = date("Y-m-d");
            if ($data['type'] === 'last_7days') {
                $startDate = date("Y-m-d", strtotime("-7 days"));
            } elseif ($data['type'] === 'last_2weeks') {
                $startDate = date("Y-m-d", strtotime("-14 days"));
            } elseif ($data['type'] === 'last_month') {
                $startDate = date("Y-m-d", strtotime("-1 month"));
            }
        }

        DB::connection('mysql')->select('SET sql_mode = "";');
        $query = "
        SELECT DATE(createdAt) AS order_date,
               SUM(price_total_final) AS total_price
        FROM db_master.rs_orders
        WHERE createdAt >= '" . $startDate . " 00:00:00' AND createdAt <= '" . $endDate . " 23:59:59'
        AND company_id = " . $companyId . "
        GROUP BY DATE(createdAt);
            ";
        $queryData = DB::SELECT($query);

        $queryRight = "
        WITH RECURSIVE HourlySeries AS (
          SELECT 0 AS hour_of_day
          UNION
          SELECT hour_of_day + 1
          FROM HourlySeries
          WHERE hour_of_day < 23
        )
        SELECT 
            HourlySeries.hour_of_day AS order_hour,
            ROUND(COALESCE(COUNT(rs_orders.id), 0) / DATEDIFF('" . $endDate . "', '" . $startDate . "'), 2) AS avg_order_count_per_day,
            ROUND(COALESCE(SUM(rs_orders.price_total), 0) / DATEDIFF('" . $endDate . "', '" . $startDate . "'), 2) AS avg_total_price_per_day
        FROM 
            HourlySeries
        LEFT JOIN 
            db_master.rs_orders ON HourlySeries.hour_of_day = EXTRACT(HOUR FROM rs_orders.createdAt)
        WHERE 
            rs_orders.createdAt BETWEEN '" . $startDate . " 00:00:00' AND '" . $endDate . " 23:59:59'
            AND rs_orders.company_id = " . $companyId . "
        GROUP BY 
            HourlySeries.hour_of_day
        ORDER BY 
            HourlySeries.hour_of_day;
            ";
        $queryRightData = DB::SELECT($queryRight);

        $centerQuery = "
        SELECT DATE(createdAt) AS order_date,
               SUM(price_total_final) AS total_price
        FROM db_master.rs_orders
        WHERE DATE(createdAt) = '" . date("Y-m-d") . "'
              AND company_id = '1'
        GROUP BY DATE(createdAt);
        ";
        $queryCenterData = DB::select($centerQuery);

        return resultFunction("", true, [
            "start_date" => $startDate,
            "end_date" => $endDate,
            "type" => $data['type'],
            "left_data" => $queryData,
            "center_data" => $queryCenterData,
            "right_data" => $queryRightData
        ]);
    }
}