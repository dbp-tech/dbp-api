<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

class HrShift extends Model
{
    use HasFactory;
    use SoftDeletes;

    const CREATED_AT = 'createdAt';
    const UPDATED_AT = 'updatedAt';
    const DELETED_AT = 'deletedAt';
    
    protected $table = 'hr_shifts';

    public function getShiftDetailsAttribute($detail)
    {
        return json_decode($detail, true);
    }

    public function hr_schedule() {
        return $this->hasOne(HrSchedule::class, 'hr_shift_id', 'id');
    }
}
