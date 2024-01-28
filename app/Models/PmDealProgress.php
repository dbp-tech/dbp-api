<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PmDealProgress extends Model
{
    use HasFactory;
    use SoftDeletes;

    const CREATED_AT = 'createdAt';
    const UPDATED_AT = 'updatedAt';
    const DELETED_AT = 'deletedAt';
    
    protected $table = 'pm_deal_progress';
    protected $guarded = [];

    public function pm_pipeline() {
        return $this->hasOne(PmPipeline::class, 'id', 'pm_pipeline_id');
    }

    public function pm_stage() {
        return $this->hasOne(PmStage::class, 'id', 'pm_stage_id');
    }
}
