<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EcomProductStore extends Model
{
    use HasFactory;

    const CREATED_AT = 'createdAt';
    const UPDATED_AT = 'updatedAt';
    
    protected $table = 'ecom_product_stores';

    public function product() {
        return $this->hasOne(EcomProduct::class, 'id', 'product_id');
    }

    public function store() {
        return $this->hasOne(OcStore::class, 'id', 'store_id');
    }

    public function ecom_product_marketplace_mapping() {
        return $this->hasMany(EcomProductMarketplaceMapping::class, 'ecom_product_store_id', 'id');
    }
}
