<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class OcOrder extends Model
{
    use HasFactory;

    const CREATED_AT = 'createdAt';
    const UPDATED_AT = 'updatedAt';
    
    protected $table = 'oc_orders';
    protected $guarded = [];

    public function oc_order_items() {
        return $this->hasMany(OcOrderItem::class, 'order_id', 'order_id');
    }

    public function oc_invoice() {
        return $this->hasOne(OcInvoice::class, 'order_id', 'order_id');
    }

    public function oc_customer() {
        return $this->hasOne(OcCustomer::class, 'customer_id', 'customer_id');
    }

    public function oc_payment_detail() {
        return $this->hasOne(OcPaymentDetail::class, 'order_id', 'order_id');
    }

    public function oc_shipping_detail() {
        return $this->hasOne(OcShippingDetail::class, 'order_id', 'order_id');
    }

    public function oc_statuses() {
        return $this->hasMany(OcStatus::class, 'order_id', 'order_id');
    }
}
