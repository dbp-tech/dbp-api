<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class RsMenu extends Model
{
    use HasFactory;
    use SoftDeletes;

    const CREATED_AT = 'createdAt';
    const UPDATED_AT = 'updatedAt';
    const DELETED_AT = 'deletedAt';
    
    protected $table = 'rs_menus';
    protected $guarded = [];

    public function rs_category() {
        return $this->hasOne(RsCategory::class, 'id', 'rs_category_id');
    }

    public function rs_menu_recipes() {
        return $this->hasMany(RsMenuRecipe::class, 'rs_menu_id', 'id');
    }

    public function rs_menu_prices() {
        return $this->hasMany(RsMenuPrice::class, 'rs_menu_id', 'id');
    }

    public function rs_addons_category_menus() {
        return $this->hasMany(RsAddonsCategoryMenu::class, 'rs_menu_id', 'id');
    }
}
