<?php

namespace App\Repositories;

use App\Models\HrAttendance;
use App\Models\Company;
use App\Models\HrAttendanceAdjustment;
use App\Models\HrAttendanceDetail;
use App\Models\HrCompanySetting;
use App\Models\HrCorrection;
use App\Models\HrCorrectionApproval;
use App\Models\HrEmployee;
use App\Models\HrEmployeeSchedule;
use App\Models\HrLeaveCategory;
use App\Models\HrLeaveRequest;
use App\Models\HrLeaveRequestApproval;
use App\Models\HrSchedule;
use App\Models\HrScheduleRotation;
use App\Models\HrShift;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class HrRepository
{
    public function employeeIndex($filters, $companyId)
    {
        $employees = HrEmployee::with(['user']);
        $employees = $employees->where('company_id', $companyId);
        $employees = $employees->orderBy('id', 'desc')->paginate(25);

        return $employees;
    }

    public function employeeSave($data, $companyId)
    {
        try {
            $validator = Validator::make($data, [
                'user_uid' => 'required',
                'dob' => 'required',
                'join_date' => 'required',
                'gender' => 'required',
                'id_number' => 'required',
                'id_tax_number' => 'required',
                'status' => 'required',
                'religion' => 'required',
            ]);
            if ($validator->fails()) return resultFunction('Err code HR-ES: validation err ' . $validator->errors());

            $company = Company::find($companyId);
            if (!$company) return resultFunction('Err code HR-ES: company not found');

            $user = User::where('user_uid', $data['user_uid'])->first();
            if (!$user) return resultFunction('Err code HR-ES: user not found');

            $employee = HrEmployee::with([])
                ->where('user_id', $user->id)
                ->first();
            if (!$employee) {
                $employee = new HrEmployee();
                $employee->company_id = $companyId;
                $employee->user_id = $user->id;
            }
            $employee->dob = $data['dob'];
            $employee->join_date = $data['join_date'];
            $employee->gender = $data['gender'];
            $employee->id_number = $data['id_number'];
            $employee->id_tax_number = $data['id_tax_number'];
            $employee->status = $data['status'];
            $employee->religion = $data['religion'];
            $employee->save();

            return resultFunction("Success to save employee", true);
        } catch (\Exception $e) {
            return resultFunction("Err code HR-ES catch: " . $e->getMessage());
        }
    }

    public function attendanceIndex($filters, $companyId)
    {
        $attendance = HrAttendance::with(['user', 'hr_attendance_details', 'hr_attendance_adjustments', 'hr_employee']);
        if (!empty($filters['hr_employee_id'])) {
            $attendance = $attendance->where('hr_employee_id', 'LIKE', '%' . $filters['hr_employee_id'] . '%');
        }        

        $attendance = $attendance->where('company_id', $companyId);
        $attendance = $attendance->orderBy('id', 'desc')->paginate(25);

        return $attendance;
    }

    public function attendanceSave($data, $companyId, $user)
    {
        try {
            $validator = Validator::make($data, [
                /*'period_date' => 'required',
                'period_month' => 'required',
                'period_year' => 'required',
                'clock_in' => 'required',
                'clock_out' => 'required',
                'image_in' => 'required',
                'image_out' => 'required',
                'latitude_in' => 'required',
                'latitude_out' => 'required',
                'longitude_in' => 'required',
                'longitude_out' => 'required',
                'late_count' => 'required',
                'attendance_status' => 'required',*/
                'image' => 'required',
                'latitude' => 'required',
                'longitude' => 'required',
            ]);
            if ($validator->fails()) return resultFunction('Err code HR-AS: validation err ' . $validator->errors());

            $company = Company::find($companyId);
            if (!$company) return resultFunction('Err code HR-AS: company not found');

            if (!$user) return resultFunction('Err code HR-AS: user not found');

            if (!$user->hr_employee) return resultFunction("Err code HR-AS: employee not found for user");

            $attendance = HrAttendance::where('period_date', date("d"))
                ->where('period_month', date('m'))
                ->where('period_year', date('Y'))
                ->first();
            if (!$attendance) {
                $attendance = new HrAttendance();
                $attendance->company_id = $company->id;
                $attendance->hr_employee_id = $user->hr_employee->id;
                $attendance->period_date = date("d");
                $attendance->period_month = date("m");
                $attendance->period_year = date("Y");
                $attendance->image_in = null;
                $attendance->latitude_in = null;
                $attendance->longitude_in = null;
                $attendance->image_out = null;
                $attendance->latitude_out = null;
                $attendance->longitude_out = null;
            }

            $clockType = null;
            $timeNow = date("H:i:s");
            if ($timeNow > '16:00:00' AND $timeNow < '20:00:00') {
                $clockType = 'clock_out';
            } elseif ($timeNow > '07:00:00' AND $timeNow < '11:00:00') {
                $clockType = 'clock_in';
            }

            if (!$clockType) return resultFunction("Err code HR-AS: please clock in (7-10) and clock out (16-20)");

            if ($clockType === 'clock_in') {
                $attendance->clock_in = $timeNow;
                $attendance->image_in = $data['image'];
                $attendance->latitude_in = $data['latitude'];
                $attendance->longitude_in = $data['longitude'];
            } else {
                $attendance->clock_out = $timeNow;
                $attendance->image_out = $data['image'];
                $attendance->latitude_out = $data['latitude'];
                $attendance->longitude_out = $data['longitude'];
            }
            $attendance->late_count = 0;
            $attendance->save();

            return resultFunction("Success to save attendance", true, $attendance);
        } catch (\Exception $e) {
            return resultFunction("Err code HR-AS catch: " . $e->getMessage());
        }
    }

    public function attendanceDetail($id, $companyId)
    {
        try {
            $attendance = HrAttendance::with(['hr_attendance_details', 'hr_attendance_adjustments'])->find($id);
            if (!$attendance) return resultFunction("Err code HR-AD: attendance not found");

            if ($attendance->company_id != $companyId) return resultFunction("Err code HR-AD: the attendance is not belongs to you");

            return resultFunction("", true, $attendance);
        } catch (\Exception $e) {
            return resultFunction("Err code HR-AD catch: " . $e->getMessage());
        }
    }

    public function attendanceDetailSave($data, $companyId, $user)
    {
        try {
            $validator = Validator::make($data, [
                'hr_attendance_id' => 'required',
                'clock_in' => 'required',
                'clock_out' => 'required',
                'type' => 'required',
            ]);
            if ($validator->fails()) return resultFunction('Err code HR-ADS: validation err ' . $validator->errors());

            $company = Company::find($companyId);
            if (!$company) return resultFunction('Err code HR-ADS: company not found');

            if (!$user) return resultFunction('Err code HR-ADS: user not found');

            if (!$user->hr_employee) return resultFunction("Err code HR-ADS: employee not found for user");

            $attendance = HrAttendance::find($data['hr_attendance_id']);
            if (!$attendance) return resultFunction("Err code HR-ADS: attendance not found");

            if ($attendance->hr_employee_id !== $user->hr_employee->id) return resultFunction("Err code HR-ADS: the attendance is not belongs to you");

            $attendanceDetail = new HrAttendanceDetail();
            $attendanceDetail->company_id = $company->id;
            $attendanceDetail->hr_employee_id = $user->hr_employee->id;
            $attendanceDetail->hr_attendance_id = $attendance->id;
            $attendanceDetail->clock_in =  $data['clock_in'];
            $attendanceDetail->clock_out =  $data['clock_out'];
            $attendanceDetail->type =  $data['type'];
            $attendanceDetail->notes =  $data['notes'];
            $attendanceDetail->save();

            return resultFunction("Success to save  detail", true, $attendanceDetail);
        } catch (\Exception $e) {
            return resultFunction("Err code HR-ADS catch: " . $e->getMessage());
        }
    }

    public function attendanceDetailDelete($attendanceId, $attendanceDetailId, $user)
    {
        try {
            $attendance = HrAttendance::with([])->find($attendanceId);
            if (!$attendance) return resultFunction("Err code HR-ADD: attendance not found");

            if (!$user) return resultFunction('Err code HR-ADS: user not found');

            if (!$user->hr_employee) return resultFunction("Err code HR-ADS: employee not found for user");

            if ($attendance->hr_employee_id != $user->hr_employee->id) return resultFunction("Err code HR-ADS: the attendance is not belongs to you");

            $attendanceDetail = HrAttendanceDetail::with([])->find($attendanceDetailId);
            if (!$attendanceDetail) return resultFunction("Err code HR-ADD: attendance detail not found");

            if ($attendanceDetail->hr_attendance_id != $attendance->id) return resultFunction("Err code HR-ADD: the attendance detail is not belongs to you");

            $attendanceDetail->delete();

            return resultFunction("Success to delete the attendance detail", true);
        } catch (\Exception $e) {
            return resultFunction("Err code HR-ADD catch: " . $e->getMessage());
        }
    }

    public function attendanceAdjustmentSave($data, $companyId, $user)
    {
        try {
            $validator = Validator::make($data, [
                'hr_attendance_id' => 'required',
                'original_clock_in' => 'required',
                'original_clock_out' => 'required',
                'adjusted_clock_in' => 'required',
                'adjusted_clock_out' => 'required',
                'adjustment_reason' => 'required',
                'adjusted_by' => 'required',
                'adjustment_date' => 'required',
            ]);
            if ($validator->fails()) return resultFunction('Err code HR-AJS: validation err ' . $validator->errors());

            $company = Company::find($companyId);
            if (!$company) return resultFunction('Err code HR-AJS: company not found');

            if (!$user) return resultFunction('Err code HR-AJS: user not found');

            if (!$user->hr_employee) return resultFunction("Err code HR-AJS: employee not found for user");

            $attendance = HrAttendance::find($data['hr_attendance_id']);
            if (!$attendance) return resultFunction("Err code HR-AJS: attendance not found");

            if ($attendance->hr_employee_id !== $user->hr_employee->id) return resultFunction("Err code HR-AJS: the attendance is not belongs to you");

            $adjustedEmployee = HrEmployee::find($data['adjusted_by']);
            if (!$adjustedEmployee) return resultFunction("Err code HR-AJS: the adjusted employee not found");

            $attendanceAdjustment = new HrAttendanceAdjustment();
            $attendanceAdjustment->company_id = $company->id;
            $attendanceAdjustment->hr_attendance_id = $attendance->id;
            $attendanceAdjustment->original_clock_in =  $data['original_clock_in'];
            $attendanceAdjustment->original_clock_out =  $data['original_clock_out'];
            $attendanceAdjustment->adjusted_clock_in =  $data['adjusted_clock_in'];
            $attendanceAdjustment->adjusted_clock_out =  $data['adjusted_clock_out'];
            $attendanceAdjustment->adjustment_reason =  $data['adjustment_reason'];
            $attendanceAdjustment->adjusted_by =  $data['adjusted_by'];
            $attendanceAdjustment->adjustment_date =  $data['adjustment_date'];
            $attendanceAdjustment->save();

            return resultFunction("Success to save  adjustment", true, $attendanceAdjustment);
        } catch (\Exception $e) {
            return resultFunction("Err code HR-AJS catch: " . $e->getMessage());
        }
    }

    public function attendanceAdjustmentDelete($attendanceId, $attendanceAdjustmentId, $user)
    {
        try {
            $attendance = HrAttendance::with([])->find($attendanceId);
            if (!$attendance) return resultFunction("Err code HR-ADD: attendance not found");

            if (!$user) return resultFunction('Err code HR-ADS: user not found');

            if (!$user->hr_employee) return resultFunction("Err code HR-ADS: employee not found for user");

            if ($attendance->hr_employee_id != $user->hr_employee->id) return resultFunction("Err code HR-ADS: the attendance is not belongs to you");

            $attendanceAdjustment = HrAttendanceAdjustment::with([])->find($attendanceAdjustmentId);
            if (!$attendanceAdjustment) return resultFunction("Err code HR-ADD: attendance adjustment not found");

            if ($attendanceAdjustment->hr_attendance_id != $attendance->id) return resultFunction("Err code HR-ADD: the attendance adjustment is not belongs to you");

            $attendanceAdjustment->delete();

            return resultFunction("Success to delete the attendance adjustment", true);
        } catch (\Exception $e) {
            return resultFunction("Err code HR-ADD catch: " . $e->getMessage());
        }
    }

    public function companySettingIndex($filters, $companyId)
    {
        $companySettings = HrCompanySetting::with([]);
        $companySettings = $companySettings->where('company_id', $companyId);
        $companySettings = $companySettings->orderBy('id', 'desc')->paginate(25);

        return $companySettings;
    }

    public function companySettingSave($data, $companyId)
    {
        try {
            $validator = Validator::make($data, [
                'module' => 'required',
                'setting_category' => 'required',
                'setting_key' => 'required',
                'setting_value' => 'required',
                'active' => 'required'
            ]);
            if ($validator->fails()) return resultFunction('Err code HR-CSS: validation err ' . $validator->errors());

            $company = Company::find($companyId);
            if (!$company) return resultFunction('Err code HR-CSS: company not found');

            if ($data['id']) {
                $companySetting = HrCompanySetting::find($data['id']);
                if (!$companySetting) return resultFunction("Err code HR-CSS: company setting not found");
            } else {
                $companySetting = new HrCompanySetting();
                $companySetting->company_id = $company->id;
            }

            $companySetting->setting_category = $data['setting_category'];
            $companySetting->setting_key = $data['setting_key'];
            $companySetting->setting_value = json_encode($data['setting_value']);
            $companySetting->active = $data['active'];
            $companySetting->save();

            return resultFunction("Success to save company setting", true);
        } catch (\Exception $e) {
            return resultFunction("Err code HR-CSS catch: " . $e->getMessage());
        }
    }

    public function companySettingDelete($id, $companyId) {
        try {
            $companySetting = HrShift::find($id);
            if (!$companySetting) return resultFunction("Err code HR-CSD: companySetting not found");

            if ($companySetting->company_id != $companyId) return resultFunction("Err code HR-CSD: the companySetting is not belongs to you");

            $companySetting->delete();

            return resultFunction("Sucess to delete companySetting", true);
        } catch (\Exception $e) {
            return resultFunction("Err code HR-CSD catch: " . $e->getMessage());
        }
    }

    public function shiftIndex($filters, $companyId)
    {
        $shifts = HrShift::with([]);
        $shifts = $shifts->where('company_id', $companyId);
        $shifts = $shifts->orderBy('id', 'desc')->paginate(25);

        return $shifts;
    }

    public function shiftSave($data, $companyId)
    {
        try {
            $validator = Validator::make($data, [
                'shift_name' => 'required',
                'shift_description' => 'required',
                'shift_type' => 'required'
            ]);
            if ($validator->fails()) return resultFunction('Err code HR-SS: validation err ' . $validator->errors());

            $company = Company::find($companyId);
            if (!$company) return resultFunction('Err code HR-SS: company not found');

            if ($data['id']) {
                $shift = HrShift::find($data['id']);
                if (!$shift) return resultFunction("Err code HR-SS: shift not found");
            } else {
                $shift = new HrShift();
                $shift->company_id = $company->id;
            }

            $shift->shift_name = $data['shift_name'];
            $shift->shift_description = $data['shift_description'];
            $shift->shift_type = $data['shift_type'];
            $shift->shift_details = '[]';
            if (in_array($data['shift_type'], ['split', 'flexible'])) {
                $shift->shift_details = json_encode($data['shift_details']);
            }
            $shift->save();

            return resultFunction("Success to save shift", true);
        } catch (\Exception $e) {
            return resultFunction("Err code HR-SS catch: " . $e->getMessage());
        }
    }

    public function shiftDelete($id, $companyId) {
        try {
            $shift = HrShift::with([])->find($id);
            if (!$shift) return resultFunction("Err code HR-SD: shift not found");

            if ($shift->company_id != $companyId) return resultFunction("Err code HR-SD: the shift is not belongs to you");

            if ($shift->hr_schedule) return resultFunction("Err code HR-SD: the shift has already schedule, please delete all schedule of this shift");

            $shift->delete();

            return resultFunction("Success to delete shift", true);
        } catch (\Exception $e) {
            return resultFunction("Err code HR-SD catch: " . $e->getMessage());
        }
    }

    public function scheduleIndex($filters, $companyId)
    {
        $schedules = HrSchedule::with(['hr_shift']);
        $schedules = $schedules->where('company_id', $companyId);
        $schedules = $schedules->orderBy('id', 'desc')->paginate(25);

        return $schedules;
    }

    public function scheduleSave($data, $companyId)
    {
        try {
            $validator = Validator::make($data, [
                'hr_shift_id' => 'required',
                'pattern_type' => 'required'
            ]);
            if ($validator->fails()) return resultFunction('Err code HR-SS: validation err ' . $validator->errors());

            $company = Company::find($companyId);
            if (!$company) return resultFunction('Err code HR-SS: company not found');

            $shift = Company::find($data['hr_shift_id']);
            if (!$shift) return resultFunction('Err code HR-SS: shift not found');

            if ($data['id']) {
                $schedule = HrSchedule::find($data['id']);
                if (!$schedule) return resultFunction("Err code HR-SS: schedule not found");
            } else {
                $schedule = new HrSchedule();
                $schedule->company_id = $company->id;
            }

            $schedule->hr_shift_id = $shift->id;
            $schedule->pattern_type = $data['pattern_type'];
            $schedule->pattern_details = '[]';
            if ($data['pattern_type'] === 'fixed') {
                $schedule->date = $data['date'];
            } elseif (in_array($data['pattern_type'], ['daily', 'weekly'])) {
                $schedule->pattern_details = json_encode($data['pattern_details']);
            }
            $schedule->save();

            return resultFunction("Success to save schedule", true);
        } catch (\Exception $e) {
            return resultFunction("Err code HR-SS catch: " . $e->getMessage());
        }
    }

    public function scheduleDelete($id, $companyId) {
        try {
            $schedule = HrSchedule::find($id);
            if (!$schedule) return resultFunction("Err code HR-SD: schedule not found");

            if ($schedule->company_id != $companyId) return resultFunction("Err code HR-SD: the schedule is not belongs to you");

            $schedule->delete();

            return resultFunction("Sucess to delete schedule", true);
        } catch (\Exception $e) {
            return resultFunction("Err code HR-SD catch: " . $e->getMessage());
        }
    }

    public function scheduleRotationIndex($filters, $companyId)
    {
        $scheduleRotations = HrScheduleRotation::with([]);
        $scheduleRotations = $scheduleRotations->where('company_id', $companyId);
        $scheduleRotations = $scheduleRotations->orderBy('id', 'desc')->paginate(25);

        return $scheduleRotations;
    }

    public function scheduleRotationSave($data, $companyId)
    {
        try {
            $validator = Validator::make($data, [
                'name' => 'required',
                'rotation_logic' => 'required'
            ]);
            if ($validator->fails()) return resultFunction('Err code HR-SRS: validation err ' . $validator->errors());

            $company = Company::find($companyId);
            if (!$company) return resultFunction('Err code HR-SRS: company not found');

            if ($data['id']) {
                $scheduleRotation = HrScheduleRotation::find($data['id']);
                if (!$scheduleRotation) return resultFunction("Err code HR-SRS: schedule rotation not found");
            } else {
                $scheduleRotation = new HrScheduleRotation();
                $scheduleRotation->company_id = $company->id;
            }

            $scheduleRotation->name = $data['name'];
            $scheduleRotation->rotation_logic = json_encode($data['rotation_logic']);
            $scheduleRotation->save();

            return resultFunction("Success to save schedule rotation", true);
        } catch (\Exception $e) {
            return resultFunction("Err code HR-SRS catch: " . $e->getMessage());
        }
    }

    public function scheduleRotationDelete($id, $companyId) {
        try {
            $scheduleRotation = HrScheduleRotation::find($id);
            if (!$scheduleRotation) return resultFunction("Err code HR-SRD: schedule rotation not found");

            if ($scheduleRotation->company_id != $companyId) return resultFunction("Err code HR-SRD: the schedule rotation is not belongs to you");

            $scheduleRotation->delete();

            return resultFunction("Success to delete schedule", true);
        } catch (\Exception $e) {
            return resultFunction("Err code HR-SRD catch: " . $e->getMessage());
        }
    }

    public function employeeScheduleSave($data, $companyId)
    {
        try {
            $validator = Validator::make($data, [
                'hr_employee_id' => 'required',
                'type' => 'required',
                'status' => 'required',
                'effective_date' => 'required'
            ]);
            if ($validator->fails()) return resultFunction('Err code HR-ESS: validation err ' . $validator->errors());

            $company = Company::find($companyId);
            if (!$company) return resultFunction('Err code HR-ESS: company not found');

            $hrEmployee = HrEmployee::find($data['hr_employee_id']);
            if (!$hrEmployee) return resultFunction('Err code HR-ESS: employee not found');

            if ($data['id']) {
                $employeeSchedule = HrEmployeeSchedule::find($data['id']);
                if (!$employeeSchedule) return resultFunction("Err code HR-ESS: schedule rotation not found");
            } else {
                $employeeSchedule = new HrEmployeeSchedule();
                $employeeSchedule->company_id = $company->id;
                $employeeSchedule->hr_employee_id = $hrEmployee->id;
            }

            if ($data['type'] === 'schedule') {
                $hrSchedule = HrSchedule::find($data['type_id']);
                if (!$hrSchedule) return resultFunction('Err code HR-ESS: schedule not found');

                $employeeSchedule->hr_schedule_id = $data["type_id"];
                $employeeSchedule->hr_schedule_rotation_id = null;
            } else {
                $hrScheduleRotation = HrScheduleRotation::find($data['type_id']);
                if (!$hrScheduleRotation) return resultFunction('Err code HR-ESS: schedule rotation not found');

                $employeeSchedule->hr_schedule_id = null;
                $employeeSchedule->hr_schedule_rotation_id = $data["type_id"];
            }

            $employeeSchedule->status = $data['status'];
            $employeeSchedule->effective_date = $data['effective_date'];
            $employeeSchedule->save();

            return resultFunction("Success to save employee schedule", true);
        } catch (\Exception $e) {
            return resultFunction("Err code HR-ESS catch: " . $e->getMessage());
        }
    }
}

