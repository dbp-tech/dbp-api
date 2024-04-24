<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class RoleSystemModule extends Model
{
    use HasFactory;

    const CREATED_AT = 'createdAt';
    const UPDATED_AT = 'updatedAt';
    
    protected $table = 'role_system_modules';
    protected $guarded = [];

    public function system_module() {
        return $this->hasOne(SystemModule::class,'id', 'system_module_id');
    }
}
