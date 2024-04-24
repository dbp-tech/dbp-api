<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class RsOutletStation extends Model
{
    use HasFactory;

    const CREATED_AT = 'createdAt';
    const UPDATED_AT = 'updatedAt';
    
    protected $table = 'rs_outlet_stations';
    protected $guarded = [];

    public function rs_outlet() {
        return $this->hasOne(RsOutlet::class,  'id', 'rs_outlet_id');
    }

    public function rs_station() {
        return $this->hasOne(RsStation::class,  'id', 'rs_station_id');
    }
}
