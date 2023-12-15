<?php

namespace App\Repositories;

use App\Models\Attendance;
use App\Models\Company;
use Illuminate\Support\Facades\Validator;

class AttendanceRepository
{
    public function index($filters, $companyId)
    {
        $attendance = Attendance::with([]);
        if (!empty($filters['user_uid'])) {
            $attendance = $attendance->where('user_uid', 'LIKE', '%' . $filters['user_uid'] . '%');
        }        

        $attendance = $attendance->where('company_id', $companyId);
        $attendance = $attendance->orderBy('id', 'desc')->paginate(25);

        return $attendance;
    }

    public function save($data, $companyId)
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
                'late_count' => 'required',
            ]);
            if ($validator->fails()) return resultFunction('Err code VR-S: validation err ' . $validator->errors());

            $company = Company::find($companyId);
            if (!$company) return resultFunction('Err code VR-S: company not found');

            $attendance = new Attendance();
            $attendance->company_id = $company->id;
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

