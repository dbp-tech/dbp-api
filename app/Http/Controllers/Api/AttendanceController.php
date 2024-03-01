<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Repositories\AttendanceRepository;
use Illuminate\Http\Request;

class AttendanceController extends Controller
{
    protected $attendanceRepo;

    public function __construct()
    {
        $this->attendanceRepo = new AttendanceRepository();
    }

    public function index(Request $request)
    {
        $filters = $request->only(["user_uid", "periode"]);
        return response()->json($this->attendanceRepo->index($filters, $request->header('company_id')));
    }

    public function save(Request $request)
    {
        return response()->json($this->attendanceRepo->save($request->all(), $request->header('company_id')));
    }

}