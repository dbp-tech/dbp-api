<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\RsOrder;
use App\Models\TestMongo;
use App\Repositories\RestaurantRepository;
use App\Repositories\VariantRepository;
use Illuminate\Http\Request;

class RestaurantController extends Controller
{
    protected $restaurantRepo;

    public function __construct()
    {
        $this->restaurantRepo = new RestaurantRepository();
    }

    public function indexCategory(Request $request)
    {
        $filters = $request->only(["title", "description"]);
        return response()->json($this->restaurantRepo->indexCategory($filters, $request->header('company_id')));
    }

    public function saveCategory(Request $request)
    {
        return response()->json($this->restaurantRepo->saveCategory($request->all(), $request->header('company_id')));
    }

    public function deleteCategory(Request $request, $id = null)
    {
        return response()->json($this->restaurantRepo->deleteCategory($id, $request->header('company_id')));
    }

    public function detailCategory(Request $request, $id = null)
    {
        return response()->json($this->restaurantRepo->detailCategory($id, $request->header('company_id')));
    }

    public function indexMenu(Request $request)
    {
        $filters = $request->only(["category_id", "title", "status"]);
        return response()->json($this->restaurantRepo->indexMenu($filters, $request->header('company_id')));
    }

    public function saveMenu(Request $request)
    {
        return response()->json($this->restaurantRepo->saveMenu($request->all(), $request->header('company_id')));
    }

    public function deleteMenu(Request $request, $id = null)
    {
        return response()->json($this->restaurantRepo->deleteMenu($id, $request->header('company_id')));
    }

    public function detailMenu(Request $request, $id = null)
    {
        return response()->json($this->restaurantRepo->detailMenu($id, $request->header('company_id')));
    }

    public function indexRecipe(Request $request)
    {
        $filters = $request->only([]);
        return response()->json($this->restaurantRepo->indexRecipe($filters, $request->header('company_id')));
    }

    public function indexOutlet(Request $request)
    {
        $filters = $request->only([]);
        return response()->json($this->restaurantRepo->indexOutlet($filters, $request->header('company_id')));
    }

    public function saveOutlet(Request $request)
    {
        return response()->json($this->restaurantRepo->saveOutlet($request->all(), $request->header('company_id')));
    }

    public function deleteOutlet(Request $request, $id = null)
    {
        return response()->json($this->restaurantRepo->deleteOutlet($id, $request->header('company_id')));
    }

    public function detailOutlet(Request $request, $id = null)
    {
        return response()->json($this->restaurantRepo->detailOutlet($id, $request->header('company_id')));
    }

    public function saveOrder(Request $request)
    {
        return response()->json($this->restaurantRepo->saveOrder($request->all(), $request->header('company_id')));
    }

    public function indexOrder(Request $request)
    {
        $filters = $request->only(['order_number', 'table_number', 'order_type', 'name', 'payment_type', 'created_at']);
        return response()->json($this->restaurantRepo->indexOrder($filters, $request->header('company_id')));
    }

    public function indexMenuAddons(Request $request)
    {
        $filters = $request->only(["rs_menu_id", "title", "status"]);
        return response()->json($this->restaurantRepo->indexMenuAddons($filters, $request->header('company_id')));
    }

    public function saveMenuAddons(Request $request)
    {
        return response()->json($this->restaurantRepo->saveMenuAddons($request->all(), $request->header('company_id')));
    }

    public function deleteMenuAddons(Request $request, $id = null)
    {
        return response()->json($this->restaurantRepo->deleteMenuAddons($id, $request->header('company_id')));
    }

    public function lastWeekOrder(Request $request)
    {
        $orderData = RsOrder::where('company_id', $request->header('company_id'))
            ->where('createdAt', '>', date("Y-m-d H:i:s", strtotime('-7 days')))
            ->get();
        return count($orderData);
    }
}