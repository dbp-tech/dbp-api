<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class RsStation extends Model
{
    use HasFactory;
    use SoftDeletes;

    const CREATED_AT = 'createdAt';
    const UPDATED_AT = 'updatedAt';
    const DELETED_AT = 'deletedAt';
    
    protected $table = 'rs_stations';
    protected $guarded = [];

    public function rs_menu_stations() {
        return $this->hasMany(RsMenuStation::class, 'rs_station_id', 'id');
    }

    public function rs_outlet_stations() {
        return $this->hasMany(RsOutletStation::class, 'rs_station_id', 'id');
    }
}
