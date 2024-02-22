<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Customer extends Model
{
    use HasFactory;
    use SoftDeletes;

    const CREATED_AT = 'createdAt';
    const UPDATED_AT = 'updatedAt';
    const DELETED_AT = 'deletedAt';
    
    protected $table = 'customers';
    protected $guarded = [];

    public function customer_recipes() {
        return $this->hasMany(CustomerRecipe::class, 'customer_id', 'id');
    }

    public function company() {
        return $this->hasOne(Company::class, 'id', 'company_id');
    }
}
