<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CrmPipeline extends Model
{
    use HasFactory;
    use SoftDeletes;

    const CREATED_AT = 'createdAt';
    const UPDATED_AT = 'updatedAt';
    const DELETED_AT = 'deletedAt';
    
    protected $table = 'crm_pipelines';
    protected $guarded = [];

    public function stages() {
        return $this->hasMany(CrmStage::class, 'pipeline_id', 'id')->orderBy('pipeline_index');
    }
}
