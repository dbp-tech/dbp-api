<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

class EcomProduct extends Model
{
    use HasFactory;
    use SoftDeletes;

    const CREATED_AT = 'createdAt';
    const UPDATED_AT = 'updatedAt';
    const DELETED_AT = 'deletedAt';
    
    protected $table = 'ecom_products';

    public function product_variants() {
        return $this->hasMany(EcomProductVariant::class, 'product_id', 'id');
    }

    public function getImagesAttribute($images)
    {
        return json_decode($images, true);
    }

    public function product_category() {
        return $this->hasOne(EcomProductCategoryMapping::class, 'product_id', 'id');
    }

    public function ecom_product_marketplace_mapping() {
        return $this->hasOne(EcomProductMarketplaceMapping::class, 'product_id', 'id');
    }
}
