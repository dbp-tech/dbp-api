<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RsAddonsCategoryMenu extends Model
{
    use HasFactory;

    const CREATED_AT = 'createdAt';
    const UPDATED_AT = 'updatedAt';

    protected $table = 'rs_addons_category_menus';
    protected $guarded = [];

    public function rs_menu() {
        return $this->hasOne(RsMenu::class, 'id', 'rs_menu_id');
    }

    public function rs_addons_category() {
        return $this->hasOne(RsAddonsCategory::class, 'id', 'rs_addons_category_id');
    }
}
