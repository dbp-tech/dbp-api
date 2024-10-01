<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EcomMasterStatus extends Model
{
    use HasFactory;

    const CREATED_AT = 'createdAt';
    const UPDATED_AT = 'updatedAt';
    
    protected $table = 'ecom_master_statuses';
    protected $guarded = [];

    public function getSubStatusesAttribute($status)
    {
        return json_decode($status, true);
    }

    public function ecom_master_status_subs() {
        return $this->hasMany(EcomMasterStatusSub::class, "ecom_master_status_id", "id");
    }
}
