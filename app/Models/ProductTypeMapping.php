<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductTypeMapping extends Model
{
    use HasFactory;
    use SoftDeletes;

    const CREATED_AT = 'createdAt';
    const UPDATED_AT = 'updatedAt';
    const DELETED_AT = 'deletedAt';
    
    protected $table = 'product_type_mappings';
    protected $guarded = [];

    public function variant()
    {
        return $this->hasOne(Variant::class, 'id', 'entity_id');
    }

    public function recipe()
    {
        return $this->hasOne(Recipe::class, 'id', 'entity_id');
    }
}
