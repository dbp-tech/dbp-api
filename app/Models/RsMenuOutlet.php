<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class RsMenuOutlet extends Model
{
    use HasFactory;
    use SoftDeletes;

    const CREATED_AT = 'createdAt';
    const UPDATED_AT = 'updatedAt';
    const DELETED_AT = 'deletedAt';

    protected $table = 'rs_menu_outlets';
    protected $guarded = [];

    public function rs_menu() {
        return $this->hasOne(RsMenu::class, 'id', 'rs_menu_id');
    }

    public function rs_outlet() {
        return $this->hasMany(RsOutlet::class, 'id', 'rs_outlet_id');
    }
}
