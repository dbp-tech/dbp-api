<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

class HrSchedule extends Model
{
    use HasFactory;
    use SoftDeletes;

    const CREATED_AT = 'createdAt';
    const UPDATED_AT = 'updatedAt';
    const DELETED_AT = 'deletedAt';
    
    protected $table = 'hr_schedules';

    public function getPatternDetailsAttribute($detail)
    {
        return json_decode($detail, true);
    }

    public function hr_shift() {
        return $this->hasOne(HrShift::class, 'id', 'hr_shift_id');
    }
}
