<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Repositories\TiktokApiRepository;
use Illuminate\Http\Request;

class TiktokApiController extends Controller
{
    protected $tiktokApiRepo;

    public function __construct()
    {
        $this->tiktokApiRepo = new TiktokApiRepository();
    }

    public function orderDetail(Request $request)
    {
        return response()->json($this->tiktokApiRepo->orderDetail($request->get('order_id')));
    }

    public function webhookOrderStatusManual(Request $request)
    {
        return response()->json($this->tiktokApiRepo->webhookOrderStatus($request->all()));
    }

    public function orderIndex(Request $request)
    {
        $filters = $request->only(['start_date', 'end_date']);
        return response()->json($this->tiktokApiRepo->orderIndex($filters));
    }
}