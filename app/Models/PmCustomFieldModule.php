<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PmCustomFieldModule extends Model
{
    use HasFactory;

    const CREATED_AT = 'createdAt';
    const UPDATED_AT = 'updatedAt';
    
    protected $table = 'pm_custom_field_modules';
    protected $guarded = [];

    public function pm_custom_field() {
        return $this->hasOne(PmCustomField::class, 'id', 'pm_custom_field_id');
    }

    public function system_module() {
        return $this->hasOne(SystemModule::class, 'id', 'system_module_id');
    }

    public function getPmDetailsAttribute($optDef) {
        return json_decode($optDef, true);
    }
}
