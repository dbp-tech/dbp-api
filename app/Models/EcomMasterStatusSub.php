<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EcomMasterStatusSub extends Model
{
    use HasFactory;

    const CREATED_AT = 'createdAt';
    const UPDATED_AT = 'updatedAt';
    
    protected $table = 'ecom_master_status_subs';
    protected $guarded = [];

    public function ecom_master_status() {
        return $this->hasOne(EcomMasterStatus::class, "id", "ecom_master_status_id");
    }
}
