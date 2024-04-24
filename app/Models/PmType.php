<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PmType extends Model
{
    use HasFactory;
    use SoftDeletes;

    const CREATED_AT = 'createdAt';
    const UPDATED_AT = 'updatedAt';
    const DELETED_AT = 'deletedAt';
    
    protected $table = 'pm_types';
    protected $guarded = [];

    public function pm_type_custom_fields() {
        return $this->hasMany(PmTypeCustomField::class, 'pm_type_id', 'id');
    }

    public function pm_pipeline() {
        return $this->hasOne(PmPipeline::class, 'pm_type_id', 'id');
    }

    public function pm_stage() {
        return $this->hasOne(PmStage::class, 'pm_type_id', 'id');
    }

    public function pm_deal() {
        return $this->hasOne(PmDeal::class, 'pm_type_id', 'id');
    }
}
