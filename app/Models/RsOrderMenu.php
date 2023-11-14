<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class RsOrderMenu extends Model
{
    use HasFactory;
    use SoftDeletes;

    const CREATED_AT = 'createdAt';
    const UPDATED_AT = 'updatedAt';
    const DELETED_AT = 'deletedAt';
    
    protected $table = 'rs_order_menus';
    protected $guarded = [];

    public function rs_menu() {
        return $this->hasOne(RsMenu::class, 'id', 'rs_menu_id');
    }

    public function rs_order_menu_addons() {
        return $this->hasMany(RsOrderMenuAddon::class, 'rs_order_menu_id', 'id');
    }
}
