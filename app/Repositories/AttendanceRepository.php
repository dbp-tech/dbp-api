<?php

namespace App\Repositories;

use App\Models\Attendance;
use Illuminate\Support\Facades\Validator;

class AttendanceRepository
{
    public function index()
    {
        $attendance = Attendance::All();
        return $attendance;
    }

    public function save($data)
    {
        try {
            $validator = Validator::make($data, [
                'user_uid' => 'required',
                'periode' => 'required',
                'clock_in' => 'required',
                'clock_out' => 'required',
                'image_in' => 'required',
                'image_out' => 'required',
                'latitude_in' => 'required',
                'latitude_out' => 'required',
                'longitude_in' => 'required',
                'longitude_out' => 'required',
                'late_count' => 'required'
            ]);
            if ($validator->fails()) return resultFunction('Err code VR-S: validation err ' . $validator->errors());

            $attendance = new Attendance();
            $attendance->user_uid = $data['user_uid'];
            $attendance->periode = $data['periode'];
            $attendance->clock_in = $data['clock_in'];
            $attendance->clock_out = $data['clock_out'];
            $attendance->image_in = $data['image_in'];
            $attendance->image_out = $data['image_out'];
            $attendance->latitude_in = $data['latitude_in'];
            $attendance->latitude_out = $data['latitude_out'];
            $attendance->longitude_in = $data['longitude_in'];
            $attendance->longitude_out = $data['longitude_out'];
            $attendance->late_count = $data['late_count'];
            $attendance->save();

            return resultFunction("Success to create attendance", true, $attendance);
        } catch (\Exception $e) {
            return resultFunction("Err code VR-S catch: " . $e->getMessage());
        }
    }

}

