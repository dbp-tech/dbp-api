<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

class EcomProductCategoryMapping extends Model
{
    use HasFactory;

    const CREATED_AT = 'createdAt';
    const UPDATED_AT = 'updatedAt';
    
    protected $table = 'ecom_product_category_mapping';

    public function ecom_product_category() {
        return $this->hasOne(EcomProductCategory::class, 'id', 'category_id');
    }
}
