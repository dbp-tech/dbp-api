<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Repositories\MarketplaceRepository;
use Illuminate\Http\Request;

class MarketplaceController extends Controller
{
    protected $marketplaceRepo;

    public function __construct()
    {
        $this->marketplaceRepo = new MarketplaceRepository();
    }

    public function indexStores() {
        return response()->json($this->marketplaceRepo->indexStores());
    }

    public function indexOrders(Request $request) {
        $filters = $request->only(['store', 'invoice_number', 'start_date', 'end_date']);
        return response()->json($this->marketplaceRepo->indexOrders($filters));
    }

    public function detailOrder($id) {
        return response()->json($this->marketplaceRepo->detailOrder($id));
    }
}
