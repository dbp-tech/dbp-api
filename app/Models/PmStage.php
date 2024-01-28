<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PmStage extends Model
{
    use HasFactory;
    use SoftDeletes;

    const CREATED_AT = 'createdAt';
    const UPDATED_AT = 'updatedAt';
    const DELETED_AT = 'deletedAt';
    
    protected $table = 'pm_stages';
    protected $guarded = [];

    public function pm_stage_custom_fields() {
        return $this->hasMany(PmStageCustomField::class, 'pm_stage_id', 'id');
    }

    public function pm_pipeline() {
        return $this->hasOne(PmPipeline::class, 'id', 'pm_pipeline_id');
    }
}
