<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class RsMenuStation extends Model
{
    use HasFactory;

    const CREATED_AT = 'createdAt';
    const UPDATED_AT = 'updatedAt';
    
    protected $table = 'rs_menu_stations';
    protected $guarded = [];

    public function rs_menu() {
        return $this->hasOne(RsMenu::class,  'id', 'rs_menu_id');
    }

    public function rs_station() {
        return $this->hasOne(RsStation::class,  'id', 'rs_station_id');
    }
}
