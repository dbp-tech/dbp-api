<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

class HrAttendance extends Model
{
    use HasFactory;
    use SoftDeletes;

    const CREATED_AT = 'createdAt';
    const UPDATED_AT = 'updatedAt';
    const DELETED_AT = 'deletedAt';
    
    protected $table = 'hr_attendances';

    public function user() {
        return $this->hasOne(User::class, 'id', 'user_id');
    }

    public function hr_attendance_details() {
        return $this->hasMany(HrAttendanceDetail::class, 'hr_attendance_id', 'id');
    }

    public function hr_attendance_adjustments() {
        return $this->hasMany(HrAttendanceAdjustment::class, 'hr_attendance_id', 'id');
    }

    public function hr_employee() {
        return $this->hasOne(HrEmployee::class, 'id', 'hr_employee_id');
    }
}
