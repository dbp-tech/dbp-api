<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class RsAddonsCategory extends Model
{
    use HasFactory;
    use SoftDeletes;

    const CREATED_AT = 'createdAt';
    const UPDATED_AT = 'updatedAt';
    const DELETED_AT = 'deletedAt';

    protected $table = 'rs_addons_categories';
    protected $guarded = [];

    public function rs_menu_addons_default() {
        return $this->hasOne(RsMenuAddon::class, 'id', 'default_value');
    }

    public function rs_menu_addons() {
        return $this->hasMany(RsMenuAddon::class, 'rs_addons_category_id', 'id');
    }
}
