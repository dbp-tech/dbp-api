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

    public function ecom_products_mapping() {
        return $this->hasMany(EcomProductMarketplaceMapping::class, 'store_id', 'id');
    }
}
