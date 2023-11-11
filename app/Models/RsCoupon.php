<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class RsCoupon extends Model
{
    use HasFactory;

    const CREATED_AT = 'createdAt';
    const UPDATED_AT = 'updatedAt';
    
    protected $table = 'rs_coupons';
    protected $guarded = [];

    public function rs_coupon_menus() {
        return $this->hasMany(RsCouponMenu::class, 'coupon_id', 'id');
    }
}
