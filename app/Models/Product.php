<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use HasFactory;
    use SoftDeletes;

    const CREATED_AT = 'createdAt';
    const UPDATED_AT = 'updatedAt';
    const DELETED_AT = 'deletedAt';
    
    protected $table = 'products';
    protected $guarded = [];

    public function product_type_mapping_variants()
    {
        return $this->hasMany(ProductTypeMapping::class)->where("entity_type", 'variant');
    }

    public function product_type_mapping_recipes()
    {
        return $this->hasMany(ProductTypeMapping::class)->where("entity_type", 'recipes');
    }

    public function product_fu_templates()
    {
        return $this->hasMany(ProductFuTemplate::class);
    }
}
