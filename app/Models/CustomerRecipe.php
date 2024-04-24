<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomerRecipe extends Model
{
    use HasFactory;

    const CREATED_AT = 'createdAt';
    const UPDATED_AT = 'updatedAt';

    protected $table = 'customer_recipes';
    protected $guarded = [];

    public function recipe() {
        return $this->hasOne(Recipe::class, 'id', 'recipe_id');
    }
}
