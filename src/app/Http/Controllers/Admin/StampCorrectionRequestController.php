<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Intermission;
use App\Http\Requests\AttendanceRequest;
use App\Services\AttendanceFormatter;
use Carbon\Carbon;

class StampCorrectionRequestController extends Controller
{
    public function requestList()
    {
        $requestAttendances = Attendance::where('is_request', true)
            ->where('is_approved', false)
            ->with(['intermissions', 'user'])
            ->get();

        $approvedAttendances = Attendance::where('is_request', true)
            ->where('is_approved', true)
            ->with(['intermissions', 'user'])
            ->get();

        return view('admin_stamp_request_list', compact('requestAttendances', 'approvedAttendances'));
    }

    public function requestDetail(Attendance $attendance)
    {
        $attendance->load(['intermissions', 'user']);

        $intermissions = AttendanceFormatter::formatIntermissionsForStamp($attendance->intermissions->toArray());
        $attendanceArr = AttendanceFormatter::formatAttendanceForStamp($attendance);

        return view('admin_stamp_approve', [
            'attendance' => $attendanceArr,
            'intermissions' => $intermissions
        ]);
    }

    public function storeRequestDetail(Attendance $attendance, AttendanceRequest $request)
    {
        $date = Carbon::parse($attendance->start_at);
        $userId = $attendance->user->id;

        $attendance_records = Attendance::where('user_id', $userId)
            ->whereDate('start_at', $date)
            ->where('is_request', false)
            ->get();
        $count = $attendance_records->count();

        $recordData = [
            'user_id' => $userId,
            'start_at' => $date->copy()->setTimeFromTimeString($request->attendance_start_at),
            'finish_at' => $date->copy()->setTimeFromTimeString($request->attendance_finish_at),
            'status' => Attendance::STATUS_FINISHED,
            'comments' => $request->comments,
            'is_request' => false,
            'is_approved' => false,
        ];

        if ($count === 0) {
            $attendance_record = Attendance::create($recordData);
        } elseif ($count === 1) {
            $attendance_record = $attendance_records[0];
            $attendance_record->update($recordData);
            Intermission::where('attendance_id', $attendance_record->id)->delete();
        } else {
            return back();
        }

        if ($request->input('intermissions')) {
            foreach ($request->input('intermissions') as $intermission) {
                if ($intermission['start_at'] && $intermission['finish_at']) {
                    Intermission::create([
                        'attendance_id' => $attendance_record->id,
                        'start_at' => $date->copy()->setTimeFromTimeString($intermission['start_at']),
                        'finish_at' => $date->copy()->setTimeFromTimeString($intermission['finish_at']),
                    ]);
                }
            }
        }

        $attendance->update(['is_approved' => true]);

        return redirect('/admin/attendance/list');
    }
}
