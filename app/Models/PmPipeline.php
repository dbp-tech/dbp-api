<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PmPipeline extends Model
{
    use HasFactory;
    use SoftDeletes;

    const CREATED_AT = 'createdAt';
    const UPDATED_AT = 'updatedAt';
    const DELETED_AT = 'deletedAt';
    
    protected $table = 'pm_pipelines';
    protected $guarded = [];

    public function pm_pipeline_custom_fields() {
        return $this->hasMany(PmPipelineCustomField::class, 'pm_pipeline_id', 'id');
    }
}
