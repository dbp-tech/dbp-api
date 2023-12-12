<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Repositories\AttendanceRepository;
use Illuminate\Http\Request;

class HrisController extends Controller
{
    protected $attendanceRepo;

    public function __construct()
    {
        $this->attendanceRepo = new AttendanceRepository();
    }

    public function index(Request $request)
    {
        return response()->json($this->attendanceRepo->index());
        // return response()->json('woke');
    }

    public function save(Request $request)
    {
        return response()->json($this->attendanceRepo->save($request->all()));
    }

}