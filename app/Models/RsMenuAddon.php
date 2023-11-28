<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class RsMenuAddon extends Model
{
    use HasFactory;
    use SoftDeletes;

    const CREATED_AT = 'createdAt';
    const UPDATED_AT = 'updatedAt';
    const DELETED_AT = 'deletedAt';

    protected $table = 'rs_menu_addons';
    protected $guarded = [];

    public function rs_addons_category() {
        return $this->hasOne(RsAddonsCategory::class, 'id', 'rs_addons_category_id');
    }

    public function rs_menu_addon_recipes() {
        return $this->hasMany(RsMenuAddonRecipe::class, 'rs_menu_addon_id', 'id');
    }

    public function rs_menu_addon_prices() {
        return $this->hasMany(RsMenuAddonPrice::class, 'rs_menu_addon_id', 'id');
    }
}
