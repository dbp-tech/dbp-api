<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CrmDealPipeline extends Model
{
    use HasFactory;
    use SoftDeletes;

    const CREATED_AT = 'createdAt';
    const UPDATED_AT = 'updatedAt';
    const DELETED_AT = 'deletedAt';
    
    protected $table = 'crm_deal_pipelines';
    protected $guarded = [];

    public function pipeline() {
        return $this->hasOne(CrmPipeline::class, 'id', 'pipeline_id');
    }

    public function stage() {
        return $this->hasOne(CrmStage::class, 'id', 'stage_id');
    }

    public function customer() {
        return $this->hasOne(Customer::class, 'id', 'deal_customer');
    }
}
