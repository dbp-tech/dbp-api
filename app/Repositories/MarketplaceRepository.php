<?php

namespace App\Repositories;

use App\Models\OcOrder;
use App\Models\OcStore;

class MarketplaceRepository
{
    public function indexStores() {
        return OcStore::paginate(10);
    }

    public function indexOrders($filters) {
        $ocOrders = OcOrder::with(['oc_store']);
        if(!empty($filters["store"])) {
            $ocOrders = $ocOrders->whereHas("oc_store", function($q) use($filters) {
                return $q->where('store_name', 'LIKE', '%'.$filters["store"].'%');
            });
        }

        if(!empty($filters["invoice_number"])) {
            $ocOrders = $ocOrders->where('invoice_ref_num', 'LIKE', '%'.$filters["invoice_number"].'%');
        }

        return $ocOrders
            ->orderByDesc("id")
            ->paginate(10);
    }
}
