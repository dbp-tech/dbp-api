<?php

namespace App\Repositories;

use App\Models\Company;
use App\Models\Recipe;
use App\Models\RsCategory;
use App\Models\RsMenu;
use App\Models\RsMenuRecipe;
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
}