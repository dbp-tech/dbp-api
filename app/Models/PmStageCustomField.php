<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PmStageCustomField extends Model
{
    use HasFactory;
    use SoftDeletes;

    const CREATED_AT = 'createdAt';
    const UPDATED_AT = 'updatedAt';
    const DELETED_AT = 'deletedAt';
    
    protected $table = 'pm_stage_custom_fields';
    protected $guarded = [];

    public function pm_custom_field() {
        return $this->hasOne(PmCustomField::class, 'id', 'pm_custom_field_id');
    }
}
