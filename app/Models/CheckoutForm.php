<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CheckoutForm extends Model
{
    use HasFactory;
    use SoftDeletes;

    const CREATED_AT = 'createdAt';
    const UPDATED_AT = 'updatedAt';
    const DELETED_AT = 'deletedAt';
    
    protected $table = 'checkout_forms';
    protected $guarded = [];

    public function product()
    {
        return $this->hasOne(Product::class, 'id', 'product_id');
    }

    public function checkout_form_bump_products() {
        return $this->hasOne(CheckoutFormBumpProduct::class, 'checkout_form_id', 'id');
    }

    public function orders() {
        return $this->hasMany(Order::class, 'checkout_form_id', 'id');
    }
}
