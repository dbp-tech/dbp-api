<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PmPipelineUser extends Model
{
    use HasFactory;

    const CREATED_AT = 'createdAt';
    const UPDATED_AT = 'updatedAt';
    
    protected $table = 'pm_pipeline_users';
    protected $guarded = [];

    public function pm_pipeline() {
        return $this->hasOne(PmPipeline::class,  'id', 'pm_pipeline_id');
    }

    public function user() {
        return $this->hasOne(User::class,  'id', 'user_id');
    }
}
