<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PmCustomField extends Model
{
    use HasFactory;
    use SoftDeletes;

    const CREATED_AT = 'createdAt';
    const UPDATED_AT = 'updatedAt';
    const DELETED_AT = 'deletedAt';
    
    protected $table = 'pm_custom_fields';
    protected $guarded = [];

    public function getOptionDefaultAttribute($optDef) {
        return json_decode($optDef, true);
    }

    public function pm_custom_field_modules() {
        return $this->hasMany(PmCustomFieldModule::class, 'pm_custom_field_id', 'id');
    }
}
