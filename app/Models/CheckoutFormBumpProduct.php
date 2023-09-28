<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CheckoutFormBumpProduct extends Model
{
    use HasFactory;
    use SoftDeletes;

    const CREATED_AT = 'createdAt';
    const UPDATED_AT = 'updatedAt';
    const DELETED_AT = 'deletedAt';
    
    protected $table = 'checkout_form_bump_products';
    protected $guarded = [];

    public function product()
    {
        return $this->hasOne(Product::class, 'id', 'product_id');
    }
}
