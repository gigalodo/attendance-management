<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Attendance;
use App\Models\Intermission;

use App\Http\Requests\AttendanceRequest;

use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

//管理者用
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

        $intermissions = $attendance->intermissions->map(
            function ($intermission) {
                return [
                    'start_at'    => Carbon::parse($intermission->start_at)->format('H:i'),
                    'finish_at'   => $intermission->finish_at ? Carbon::parse($intermission->finish_at)->format('H:i') : null,
                ];
            }
        );

        $date = Carbon::parse($attendance->start_at);

        $attendance = [
            'id' => $attendance->id,
            'name' => $attendance->user->name,
            'year' => $date->format('Y年'),
            'date' => $date->format('n月j日'),
            'start_at'    => $date->format('H:i'),
            'finish_at'   => $attendance->finish_at ? Carbon::parse($attendance->finish_at)->format('H:i') : null,
            'comments' => $attendance->comments,
            'is_approved' => $attendance->is_approved,
        ];

        return view('admin_stamp_approve', compact('attendance', 'intermissions'));
    }

    public function storeRequestDetail(Attendance $attendance, AttendanceRequest $request)
    {

        $date = Carbon::parse($attendance->start_at);

        $attendance_records = Attendance::where('user_id', $attendance->user->id)
            ->whereDate('start_at', $date)
            ->where('is_request', false)
            ->get();

        if ($attendance_records->count() !== 1) {
            return back();
        }

        $attendance_record = $attendance_records[0];
        $attendance_record->update([
            'start_at' =>  $date->copy()->setTimeFromTimeString($request->attendance_start_at),
            'finish_at' =>  $date->copy()->setTimeFromTimeString($request->attendance_finish_at),
            'status' => Attendance::STATUS_FINISHED,
            'comments' => $request->comments,
        ]);

        $attendance->update([
            'is_approved' => true,
        ]);

        Intermission::where('attendance_id', $attendance_record->id)->delete();

        if ($request->input('intermissions')) {
            foreach ($request->input('intermissions') as $intermission) {
                if ($intermission['start_at'] && $intermission['finish_at']) {
                    Intermission::create([
                        'attendance_id' => $attendance_record->id,
                        'start_at' =>  $date->copy()->setTimeFromTimeString($intermission['start_at']),
                        'finish_at' => $date->copy()->setTimeFromTimeString($intermission['finish_at']),
                    ]);
                }
            }
        }

        return redirect('/admin/attendance/list');
    }
}
