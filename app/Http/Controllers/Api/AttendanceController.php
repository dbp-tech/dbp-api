<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Repositories\HrRepository;
use Illuminate\Http\Request;

class AttendanceController extends Controller
{
    protected $hrRepo;

    public function __construct()
    {
        $this->hrRepo = new HrRepository();
    }

    public function employeeIndex(Request $request)
    {
        $filters = $request->only([]);
        return response()->json($this->hrRepo->employeeIndex($filters, $request->header('company_id')));
    }

    public function employeeSave(Request $request)
    {
        return response()->json($this->hrRepo->employeeSave($request->all(), $request->header('company_id')));
    }

    public function attendanceIndex(Request $request)
    {
        $filters = $request->only(["hr_employee_id", "period_date", "period_month", "period_year"]);
        return response()->json($this->hrRepo->attendanceIndex($filters, $request->header('company_id')));
    }

    public function attendanceSave(Request $request)
    {
        return response()->json($this->hrRepo->attendanceSave($request->all(), $request->header('company_id'),
            json_decode($request->header('user'))));
    }

    public function attendanceDetail($id, Request $request)
    {
        return response()->json($this->hrRepo->attendanceDetail($id, $request->header('company_id')));
    }

    public function attendanceDetailSave(Request $request)
    {
        return response()->json($this->hrRepo->attendanceDetailSave($request->all(), $request->header('company_id'),
            json_decode($request->header('user'))));
    }

    public function attendanceDetailDelete($attendanceId, $attendanceDetailId, Request $request)
    {
        return response()->json($this->hrRepo->attendanceDetailDelete($attendanceId, $attendanceDetailId, json_decode($request->header('user'))));
    }

    public function companySettingIndex(Request $request)
    {
        $filters = $request->only([]);
        return response()->json($this->hrRepo->companySettingIndex($filters, $request->header('company_id')));
    }

    public function companySettingSave(Request $request)
    {
        return response()->json($this->hrRepo->companySettingSave($request->all(), $request->header('company_id')));
    }

    public function companySettingDelete(Request $request, $id)
    {
        return response()->json($this->hrRepo->companySettingDelete($id, $request->header('company_id')));
    }

    public function shiftIndex(Request $request)
    {
        $filters = $request->only([]);
        return response()->json($this->hrRepo->shiftIndex($filters, $request->header('company_id')));
    }

    public function shiftSave(Request $request)
    {
        return response()->json($this->hrRepo->shiftSave($request->all(), $request->header('company_id')));
    }

    public function shiftDelete(Request $request, $id)
    {
        return response()->json($this->hrRepo->shiftDelete($id, $request->header('company_id')));
    }

    public function shiftPerUser(Request $request)
    {
        return response()->json($this->hrRepo->shiftPerUser(json_decode($request->header('user'))));
    }

    public function scheduleIndex(Request $request)
    {
        $filters = $request->only([]);
        return response()->json($this->hrRepo->scheduleIndex($filters, $request->header('company_id')));
    }

    public function scheduleSave(Request $request)
    {
        return response()->json($this->hrRepo->scheduleSave($request->all(), $request->header('company_id')));
    }

    public function scheduleDelete(Request $request, $id)
    {
        return response()->json($this->hrRepo->scheduleDelete($id, $request->header('company_id')));
    }

    public function scheduleRotationIndex(Request $request)
    {
        $filters = $request->only([]);
        return response()->json($this->hrRepo->scheduleRotationIndex($filters, $request->header('company_id')));
    }

    public function scheduleRotationSave(Request $request)
    {
        return response()->json($this->hrRepo->scheduleRotationSave($request->all(), $request->header('company_id')));
    }

    public function scheduleRotationDelete(Request $request, $id)
    {
        return response()->json($this->hrRepo->scheduleRotationDelete($id, $request->header('company_id')));
    }

    public function employeeScheduleSave(Request $request)
    {
        return response()->json($this->hrRepo->employeeScheduleSave($request->all(), $request->header('company_id')));
    }

    public function scheduleExceptionSave(Request $request)
    {
        return response()->json($this->hrRepo->scheduleExceptionSave($request->all(), $request->header('company_id')));
    }

    public function scheduleExceptionIndex(Request $request)
    {
        $filters = $request->only([]);
        return response()->json($this->hrRepo->scheduleExceptionIndex($filters, $request->header('company_id')));
    }

    public function scheduleExceptionSaveApproval(Request $request)
    {
        return response()->json($this->hrRepo->scheduleExceptionSaveApproval($request->all(), $request->header('company_id')));
    }
}