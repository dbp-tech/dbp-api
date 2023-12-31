<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\SaveTitktokDb;
use App\Jobs\SaveTokpedNotificationDb;
use App\Jobs\SaveTokpedStatusDb;
use App\Repositories\CompanyAccountRepository;
use App\Repositories\TokopediaApiRepository;
use Illuminate\Http\Request;

class TokopediaApiController extends Controller
{
    protected $tokopediaApiRepo;

    public function __construct()
    {
        $this->tokopediaApiRepo = new TokopediaApiRepository();
    }

    public function indexCategory(Request $request)
    {
        return response()->json($this->tokopediaApiRepo->indexCategory($request->all()));
    }

    public function createProduct(Request $request)
    {
        return response()->json($this->tokopediaApiRepo->createProduct($request->all()));
    }

    public function getShopInfo()
    {
        return response()->json($this->tokopediaApiRepo->getShopInfo());
    }

    public function getShowcase($shopId)
    {
        return response()->json($this->tokopediaApiRepo->getShowcase($shopId));
    }

    public function indexProduct()
    {
        return response()->json($this->tokopediaApiRepo->indexProduct());
    }

    public function deleteProduct($id)
    {
        return response()->json($this->tokopediaApiRepo->deleteProduct($id));
    }

    public function detailProduct($id)
    {
        return response()->json($this->tokopediaApiRepo->detailProduct($id));
    }

    public function indexOrder(Request $request)
    {
        return response()->json($this->tokopediaApiRepo->indexOrder($request->all()));
    }

    public function detailOrder($orderId)
    {
        return response()->json($this->tokopediaApiRepo->detailOrder($orderId));
    }

    public function testTiktokBulk(Request $request) {
        dispatch(new SaveTitktokDb($request->all()));
        return response()->json('oke');
    }

    public function webhookOrderNotificationManual(Request $request)
    {
        return response()->json($this->tokopediaApiRepo->webhookOrderNotification($request->all()));
    }

    public function webhookOrderNotification(Request $request)
    {
        dispatch(new SaveTokpedNotificationDb($request->all()));
        return response()->json(resultFunction("Success hit order notification", true));
    }

    public function webhookOrderStatusManual(Request $request)
    {
        return response()->json($this->tokopediaApiRepo->webhookOrderStatus($request->all()));
    }

    public function webhookOrderStatus(Request $request)
    {
        dispatch(new SaveTokpedStatusDb($request->all()));
        return response()->json(resultFunction("Success hit order status", true));
    }
}