<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OcStore extends Model
{
    use HasFactory;

    const CREATED_AT = 'createdAt';
    const UPDATED_AT = 'updatedAt';
    
    protected $table = 'oc_stores';
    protected $guarded = [];

    public function ecom_product_stores() {
        return $this->hasMany(EcomProductStore::class, 'store_id', 'id');
    }

    public function oc_orders() {
        return $this->hasMany(OcOrder::class, 'store_id', 'store_id')->orderBy('createdAt', 'desc');
    }
}
