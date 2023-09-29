<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CrmDeal extends Model
{
    use HasFactory;
    use SoftDeletes;

    const CREATED_AT = 'createdAt';
    const UPDATED_AT = 'updatedAt';
    const DELETED_AT = 'deletedAt';
    
    protected $table = 'crm_deals';
    protected $guarded = [];

    public function deal_pipeline() {
        return $this->hasOne(CrmDealPipeline::class, 'deal_id', 'id')->orderBy('id', 'desc');
    }

    public function deal_pipelines() {
        return $this->hasMany(CrmDealPipeline::class, 'deal_id', 'id')->orderBy('id', 'desc');
    }
}
