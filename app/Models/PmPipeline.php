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

    public function pm_type() {
        return $this->hasOne(PmType::class, 'id', 'pm_type_id');
    }

    public function pm_stages() {
        return $this->hasMany(PmStage::class, 'pm_pipeline_id', 'id');
    }

    public function pm_pipeline_users() {
        return $this->hasMany(PmPipelineUser::class, 'pm_pipeline_id', 'id');
    }
}
