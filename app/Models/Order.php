<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Order extends Model
{
    use HasFactory;
    use SoftDeletes;

    const CREATED_AT = 'createdAt';
    const UPDATED_AT = 'updatedAt';
    const DELETED_AT = 'deletedAt';
    
    protected $table = 'orders';
    protected $guarded = [];

    public function order_details()
    {
        return $this->hasMany(OrderDetail::class, 'order_id', 'id');
    }

    public function order_informations()
    {
        return $this->hasMany(OrderInformation::class, 'order_id', 'id');
    }

    public function order_fu_histories()
    {
        return $this->hasMany(OrderFuHistory::class, 'order_id', 'id');
    }

    public function order_statuses()
    {
        return $this->hasMany(OrderStatus::class, 'order_id', 'id');
    }

    public function checkout_form()
    {
        return $this->hasOne(CheckoutForm::class, 'id', 'checkout_form_id');
    }
}
