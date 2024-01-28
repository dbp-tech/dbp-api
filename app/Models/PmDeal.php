<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PmDeal extends Model
{
    use HasFactory;
    use SoftDeletes;

    const CREATED_AT = 'createdAt';
    const UPDATED_AT = 'updatedAt';
    const DELETED_AT = 'deletedAt';
    
    protected $table = 'pm_deals';
    protected $guarded = [];

    public function pm_stage_custom_fields() {
        return $this->hasMany(PmDealCustomField::class, 'pm_deal_id', 'id');
    }

    public function pm_deal_progress() {
        return $this->hasOne(PmDealProgress::class, 'pm_deal_id', 'id');
    }
}
