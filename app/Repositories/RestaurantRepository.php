<?php

namespace App\Repositories;

use App\Models\Company;
use App\Models\Recipe;
use App\Models\RsCategory;
use App\Models\RsMenu;
use App\Models\RsMenuRecipe;
use App\Models\RsOrder;
use App\Models\RsMenuOrder;
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
            if ($validator->fails()) return resultFunction('Err code RR-S: validation err ' . $validator->errors());

            $company = Company::find($companyId);
            if (!$company) return resultFunction('Err code RR-S: company not found');

            $rsCategory = new RsCategory();
            $rsCategory->company_id = $company->id;
            $rsCategory->title = $data['title'];
            $rsCategory->description = $data['description'];
            $rsCategory->save();

            return resultFunction("Success to create category", true, $rsCategory);
        } catch (\Exception $e) {
            return resultFunction("Err code RR-S catch: " . $e->getMessage());
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
        $rsMenu = RsMenu::with(['rs_category', 'rs_menu_recipes.recipe']);
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
                'image' => 'required',
                'title' => 'required',
                'price' => 'required',
                'recipe' => 'required',
            ]);
            if ($validator->fails()) return resultFunction('Err code RR-SM: validation err ' . $validator->errors());
            DB::beginTransaction();

            $company = Company::find($companyId);
            if (!$company) return resultFunction('Err code RR-SM: company not found');

            $rsCategory = RsCategory::find($data['category']['value']);
            if (!$rsCategory) return resultFunction('Err code RR-SM: company not found');

            $rsMenu = new RsMenu();
            $rsMenu->company_id = $company->id;
            $rsMenu->rs_category_id = $rsCategory->id;
            $rsMenu->title = $data['title'];
            $rsMenu->image = $data['image']['url'];
            $rsMenu->price = $data['price'];
            $rsMenu->save();

            $rsMenuRecipes = [];
            foreach ($data['recipe'] as $recip) {
                $rsMenuRecipes[] = [
                    'rs_menu_id' => $rsMenu->id,
                    'recipe_id' => $recip['value'],
                    'createdAt' => date("Y-m-d H:i:s"),
                    'updatedAt' => date("Y-m-d H:i:s")
                ];
            }
            RsMenuRecipe::insert($rsMenuRecipes);

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
            $rsMenu =  RsMenu::with(['rs_category', 'rs_menu_recipes.recipt'])->find($id);
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

            $rsOutlet = new RsOutlet();
            $rsOutlet->company_id = $company->id;
            $rsOutlet->name = $data['name'];
            $rsOutlet->image = $data['image']['url'];
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

            if (count($data['menu_data']) == 0) return resultFunction("Err code RR-SOr: menu data is not found");

            $rsMenus = RsMenu::with([])
                ->whereIn('id', array_column($data['menu_data'], 'menu_id'))
                ->get();

            if (count($data['menu_data']) !== count($rsMenus)) return resultFunction("Err code RR-SOr: the menu data is not same with our db");
            $isEdit = false;
            if (isset($data['id'])) {
                $rsOrder = RsOrder::find($data['id']);
                if (!$rsOrder) return resultFunction('Err code RR-SOr: order not found');
                $isEdit = true;

                RsMenuOrder::where('rs_order_id', $data['id'])->delete();
            } else {
                $rsOrder = new RsOrder();
            }
            $rsOrder->company_id = $company->id;
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

            $totalPrice = 0;
            $rsOrderMenus = [];
            foreach ($rsMenus as $menu) {
                $dataMenu = (collect($data['menu_data']))->where('menu_id', $menu->id)->first();
                $rsOrderMenus[] = [
                    "rs_order_id" => $rsOrder->id,
                    "rs_menu_id" => $menu->id,
                    "quantity" => $dataMenu['quantity'],
                    "menu_title" => $menu->title,
                    "menu_price" => $menu->price,
                    "menu_image" => $menu->image,
                    "createdAt" => date("Y-m-d H:i:s"),
                    "updatedAt" => date("Y-m-d H:i:s")
                ];
                $totalPrice = $totalPrice + ($menu->price * $dataMenu['quantity']);
            }
            $rsOrder->price_total = $totalPrice;
            $rsOrder->save();

            RsMenuOrder::insert($rsOrderMenus);

            DB::commit();
            return resultFunction("Success to create order", true, $rsOrder);
        } catch (\Exception $e) {
            return resultFunction("Err code RR-SOr catch: " . $e->getMessage());
        }
    }

    public function indexOrder($filters, $companyId)
    {
        $rsOrders = RsOrder::with(['rs_menu_orders.rs_menu']);
        $rsOrders = $rsOrders->where('company_id', $companyId);
        $rsOrders = $rsOrders->orderBy('id', 'desc')->get();
        return $rsOrders;
    }
}