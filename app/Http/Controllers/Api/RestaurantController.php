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

    public function countOrder(Request $request)
    {
        return response()->json($this->restaurantRepo->countOrder($request->all(), $request->header('company_id')));
    }

    public function allMenuOrder(Request $request)
    {
        $data = $request->all();
        $data['report'] = 'total_menu_sales_all';
        return response()->json($this->restaurantRepo->countOrder($data, $request->header('company_id')));
    }

    public function indexMenuAddons(Request $request)
    {
        $filters = $request->only(["rs_addons_category_id", "title", "status"]);
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

    public function indexAddonsCategory(Request $request)
    {
        $filters = $request->only([]);
        return response()->json($this->restaurantRepo->indexAddonsCategory($filters, $request->header('company_id')));
    }

    public function saveAddonsCategory(Request $request)
    {
        return response()->json($this->restaurantRepo->saveAddonsCategory($request->all(), $request->header('company_id')));
    }

    public function deleteAddonsCategory(Request $request, $id = null)
    {
        return response()->json($this->restaurantRepo->deleteAddonsCategory($id, $request->header('company_id')));
    }

    public function indexStation(Request $request)
    {
        $filters = $request->only(["title"]);
        return response()->json($this->restaurantRepo->indexStation($filters, $request->header('company_id')));
    }

    public function saveStation(Request $request)
    {
        return response()->json($this->restaurantRepo->saveStation($request->all(), $request->header('company_id')));
    }

    public function deleteStation(Request $request, $id = null)
    {
        return response()->json($this->restaurantRepo->deleteStation($id, $request->header('company_id')));
    }

    public function detailStation(Request $request, $id = null)
    {
        return response()->json($this->restaurantRepo->detailStation($id, $request->header('company_id')));
    }

    public function assignMenuToStation(Request $request)
    {
        return response()->json($this->restaurantRepo->assignMenuToStation($request->all()));
    }

    public function assignOutletToStation(Request $request)
    {
        return response()->json($this->restaurantRepo->assignOutletToStation($request->all()));
    }

    public function saveMenuOutlet(Request $request)
    {
        return response()->json($this->restaurantRepo->saveMenuOutlet($request->all(), $request->header('company_id')));
    }

    public function saveMenuOutletPerOutlet(Request $request)
    {
        return response()->json($this->restaurantRepo->saveMenuOutletPerOutlet($request->all(), $request->header('company_id')));
    }

    public function detailMenuOutlet(Request $request, $id = null)
    {
        return response()->json($this->restaurantRepo->detailMenuOutlet($id, $request->header('company_id')));
    }
}