<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PmTypeCustomField extends Model
{
    use HasFactory;

    const CREATED_AT = 'createdAt';
    
    protected $table = 'pm_type_custom_fields';
    protected $guarded = [];

    public function pm_custom_field() {
        return $this->hasOne(PmCustomField::class, 'id', 'pm_custom_field_id');
    }
}
