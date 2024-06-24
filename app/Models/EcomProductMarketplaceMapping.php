<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EcomProductMarketplaceMapping extends Model
{
    use HasFactory;

    const CREATED_AT = 'createdAt';
    const UPDATED_AT = 'updatedAt';
    
    protected $table = 'ecom_product_marketplace_mapping';

    public function product() {
        return $this->hasOne(EcomProduct::class, 'id', 'product_id');
    }
}
