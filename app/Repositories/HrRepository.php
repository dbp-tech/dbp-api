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
use App\Models\HrScheduleException;
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
        $attendance = HrAttendance::with(['user', 'hr_attendance_details', 'hr_attendance_adjustments', 'hr_employee.user']);
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
                'image' => 'required',
                'latitude' => 'required',
                'longitude' => 'required',
            ]);
            if ($validator->fails()) return resultFunction('Err code HR-AS: validation err ' . $validator->errors());

            $company = Company::find($companyId);
            if (!$company) return resultFunction('Err code HR-AS: company not found');

            if (!$user) return resultFunction('Err code HR-AS: user not found');

            if (!$user->hr_employee) return resultFunction("Err code HR-AS: employee not found for user");

            $employee = $user->hr_employee;
            $employeeSchedule = HrEmployeeSchedule::with(['hr_schedule.hr_shift'])
                ->where('hr_employee_id', $employee->id)
                ->first();
            if (!$employeeSchedule) return resultFunction("Err code HR-AS: the employee don't have schedule, please contact admin");
            if (!$employeeSchedule->hr_schedule) return resultFunction("Err code HR-AS: your schedule data not found, please contact admin");
            if (!$employeeSchedule->hr_schedule->hr_shift) return resultFunction("Err code HR-AS: your shift data not found, please contact admin");

            $schedule = $employeeSchedule->hr_schedule;
            $shift = $schedule->hr_shift;

            // Start: check your day includes at the schedule
            $dayNow = date("l");
            if ($schedule->pattern_type === 'fixed') {
                if (!in_array($dayNow, ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'])) return resultFunction("Err code HR-AS: your schedule is fixed, and you are absent unscheduled");
            } else if ($schedule->pattern_type === 'weekly') {
                $dayWeekly = $schedule->pattern_details['days'];
                if (!in_array($dayNow, $dayWeekly)) return resultFunction("Err code HR-AS: your schedule is weekly, and you are absent unscheduled");
            }
            // End: check your day includes at the schedule

            // Start: check your hour is clock in and clock out
            $clockType = null;
            $attendanceStatus = null;
            $hourNow = date('H:i');
            if ($shift->shift_type === 'standard') {
                if ($hourNow > '06:00' AND $hourNow < '11:00') {
                    $clockType = 'clock_in';
                } elseif ($hourNow > '04:00' AND $hourNow < '20:00') {
                    $clockType = 'clock_out';
                }

                if (!$clockType) return resultFunction("Err code HR-AS: Your working hours are outside the shift hours provisions");

                if ($clockType === 'clock_in') {
                    $attendanceStatus = $hourNow < '09:01' ? 'Present' : 'Late';
                }
            } elseif ($shift->shift_type === 'flexible') {
                $detail = $shift->shift_details;
                if ($hourNow > $detail['earliest_start_time'] AND $hourNow < $detail['latest_start_time']) {
                    $attendanceStatus = 'Present';
                    $clockType = 'clock_in';
                } elseif ($hourNow > $detail['earliest_end_time'] AND $hourNow < $detail['latest_end_time']) {
                    $clockType = 'clock_out';
                }

                if ($hourNow > $detail['latest_start_time'] AND $hourNow <= date("H:i", strtotime($detail['latest_start_time']) + 2*60*60)) {
                    $clockType = 'clock_in';
                    $attendanceStatus = 'Late';
                }

                if (!$clockType) return resultFunction("Err code HR-AS: Your working hours are outside the shift hours provisions");
            }
            // End: check your hour is clock in and clock out

            $attendance = HrAttendance::where('period_date', date("d"))
                ->where('period_month', date('m'))
                ->where('period_year', date('Y'))
                ->where('hr_employee_id', $employee->id)
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

            $timeNow = date("H:i:s");
            if ($clockType === 'clock_in') {
                $attendance->clock_in = $timeNow;
                $attendance->image_in = $data['image'];
                $attendance->latitude_in = $data['latitude'];
                $attendance->longitude_in = $data['longitude'];
                $attendance->attendance_status = $attendanceStatus;
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
            $attendance = HrAttendance::with(['hr_attendance_details', 'hr_attendance_adjustments', 'hr_employee.user'])->find($id);
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
                'shift_type' => 'required',
                'shift_details' => 'required'
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
            $shift->shift_details = json_encode($data['shift_details']);
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

            $flexibleValidate = [];
            if ($data['pattern_type'] === 'fixed') {
                $flexibleValidate = [
                    'start_date' => 'required',
                    'end_date' => 'required'
                ];
            } elseif (in_array($data['pattern_type'], ['daily', 'daily'])) {
                $flexibleValidate = [
                    'pattern_details' => 'required',
                ];
            }
            $validatorFlexible = Validator::make($data, $flexibleValidate);
            if ($validatorFlexible->fails()) return resultFunction('Err code HR-SS: validation err ' . $validatorFlexible->errors());

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
            if (in_array($data['pattern_type'], ['daily', 'weekly'])) {
                $schedule->pattern_details = json_encode($data['pattern_details']);
            }

            if ($data['pattern_type'] === 'fixed') {
                $schedule->start_date = $data['start_date'];
                $schedule->end_date = $data['end_date'];
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
                'hr_schedule_id' => 'required',
                'status' => 'required',
                'effective_date' => 'required'
            ]);
            if ($validator->fails()) return resultFunction('Err code HR-ESS: validation err ' . $validator->errors());

            $company = Company::find($companyId);
            if (!$company) return resultFunction('Err code HR-ESS: company not found');

            $hrEmployee = HrEmployee::find($data['hr_employee_id']);
            if (!$hrEmployee) return resultFunction('Err code HR-ESS: employee not found');

            $hrSchedule = HrSchedule::find($data['hr_schedule_id']);
            if (!$hrSchedule) return resultFunction('Err code HR-ESS: schedule not found');

            if ($data['id']) {
                $employeeSchedule = HrEmployeeSchedule::find($data['id']);
                if (!$employeeSchedule) return resultFunction("Err code HR-ESS: schedule rotation not found");
            } else {
                $employeeSchedule = new HrEmployeeSchedule();
                $employeeSchedule->company_id = $company->id;
                $employeeSchedule->hr_employee_id = $hrEmployee->id;
            }

            if ($data['hr_schedule_rotation_id']) {
                $hrScheduleRotation = HrScheduleRotation::find($data['hr_schedule_rotation_id']);
                if (!$hrScheduleRotation) return resultFunction('Err code HR-ESS: schedule rotation not found');
                $employeeSchedule->hr_schedule_rotation_id = $data["hr_schedule_rotation_id"];
            }

            $employeeSchedule->hr_schedule_id = $data["hr_schedule_id"];
            $employeeSchedule->status = $data['status'];
            $employeeSchedule->effective_date = $data['effective_date'];
            $employeeSchedule->save();

            return resultFunction("Success to save employee schedule", true);
        } catch (\Exception $e) {
            return resultFunction("Err code HR-ESS catch: " . $e->getMessage());
        }
    }

    public function scheduleExceptionSave($data, $companyId)
    {
        try {
            $validator = Validator::make($data, [
                'exception_type' => 'required',
                'exception_detail' => 'required'
            ]);
            if ($validator->fails()) return resultFunction('Err code HR-SES: validation err ' . $validator->errors());

            $company = Company::find($companyId);
            if (!$company) return resultFunction('Err code HR-SES: company not found');

            if ($data['id']) {
                $scheduleException = HrScheduleException::find($data['id']);
                if (!$scheduleException) return resultFunction("Err code HR-SES: schedule exception not found");
            } else {
                $scheduleException = new HrScheduleException();
                $scheduleException->company_id = $company->id;
            }

            if ($data['exception_type'] === 'Leave') {
                $scheduleException->start_date = $data['exception_detail']['startDate'];
                $scheduleException->end_date = $data['exception_detail']['endDate'];
            } elseif (in_array($data['exception_type'], ['Absence', 'Overtime', 'Correction'])) {
                $scheduleException->start_date = $data['exception_detail']['date'];
                $scheduleException->end_date = $data['exception_detail']['date'];
                if ($data['exception_type'] === 'Overtime') {
                    $scheduleException->hours = $data['exception_detail']['hours'];
                }
            }

            $scheduleException->notes = $data['exception_detail']['reason'];
            $scheduleException->exception_type = $data['exception_type'];
            $scheduleException->exception_details = json_encode($data['exception_detail']);
            $scheduleException->save();

            return resultFunction("Success to save schedule exception", true);
        } catch (\Exception $e) {
            return resultFunction("Err code HR-SES catch: " . $e->getMessage());
        }
    }

    public function scheduleExceptionIndex($filters, $companyId)
    {
        $scheduleExceptions = HrScheduleException::with([]);
        $scheduleExceptions = $scheduleExceptions->where('company_id', $companyId);
        $scheduleExceptions = $scheduleExceptions->orderBy('id', 'desc')->paginate(25);

        return $scheduleExceptions;
    }
}

