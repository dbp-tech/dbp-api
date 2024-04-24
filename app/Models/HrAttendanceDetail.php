<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

class HrAttendanceDetail extends Model
{
    use HasFactory;
    use SoftDeletes;

    const CREATED_AT = 'createdAt';
    const UPDATED_AT = 'updatedAt';
    const DELETED_AT = 'deletedAt';
    
    protected $table = 'hr_attendance_details';

    public function hr_employee() {
        return $this->hasOne(HrEmployee::class, 'id', 'hr_employee_id');
    }

    public function hr_attendance() {
        return $this->hasOne(HrAttendance::class, 'hr_attendance_id', 'id');
    }
}
