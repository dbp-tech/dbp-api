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

    public function rs_menu() {
        return $this->hasOne(RsMenu::class, 'id', 'rs_category_id');
    }

    public function rs_menu_addon_recipes() {
        return $this->hasMany(RsMenuAddon::class, 'rs_menu_addon_id', 'id');
    }
}
