<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Attendance;
use App\Models\Intermission;

use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

//管理者用
class StampCorrectionRequestController extends Controller
{

    public function requestList()
    {
        $requestAttendances = Attendance::where('is_request', true)
            ->where('is_approved', false)
            ->with('intermissions')
            ->get();

        $approvedAttendances = Attendance::where('is_request', true)
            ->where('is_approved', true)
            ->with('intermissions')
            ->get();

        return view('admin_stamp_request_list', compact('requestAttendances', 'approvedAttendances'));
    }

    public function requestDetail(Attendance $attendance)
    {
        $attendance->load('intermissions');
        $intermissions = $attendance->intermissions->map(
            function ($intermission) {
                return [
                    'start_at'    => Carbon::parse($intermission->start_at)->format('H:i'),
                    'finish_at'   => $intermission->finish_at ? Carbon::parse($intermission->finish_at)->format('H:i') : null,
                ];
            }
        );


        $attendance = [
            'id' => $attendance->id,
            'name' => $attendance->user->name,
            'year' => Carbon::parse($attendance->start_at)->format('Y年'),
            'date' => Carbon::parse($attendance->start_at)->format('n月j日'),
            'start_at'    => Carbon::parse($attendance->start_at)->format('H:i'),
            'finish_at'   => $attendance->finish_at ? Carbon::parse($attendance->finish_at)->format('H:i') : null,
            'comments' => $attendance->comments,
        ];

        return view('admin_stamp_approve', compact('attendance', 'intermissions'));
    }


    public function storeRequestDetail()
    {
        $requestAttendances = Attendance::where('is_request', true)
            ->where('is_approved', false)
            ->with('intermissions')
            ->get();

        $approvedAttendances = Attendance::where('is_request', true)
            ->where('is_approved', true)
            ->with('intermissions')
            ->get();

        return view('admin_stamp_request_list', compact('requestAttendances', 'approvedAttendances'));
    }




    // 旧式　不要
    public function index()
    {
        $date = Carbon::now();

        // $users = User::where('role', 'user')
        //     ->with('attendances')
        //     ->whereDate('start_at', $date)
        //     ->get();

        $users = User::where('role', 'user')
            ->with(['attendances' => function ($query) use ($date) {
                $query->whereDate('start_at', $date)
                    ->whereDate('is_request', false)
                    ->with('intermissions');
            }])
            ->get();

        dd($users);

        $attendances = Attendance::where('user_id', Auth::id())
            ->where('is_request', false)
            ->whereBetween('start_at', [
                Carbon::now()->startOfMonth(),
                Carbon::now()->endOfMonth()
            ])
            ->with('intermissions')
            ->get()
            ->map(function ($attendance) {

                $restMinutes = $attendance->intermissions->sum(function ($intermission) {
                    if ($intermission->finish_at) {
                        return Carbon::parse($intermission->finish_at)
                            ->diffInMinutes(Carbon::parse($intermission->start_at));
                    }
                    return 0;
                });

                $workMinutes = 0;
                if ($attendance->finish_at) {
                    $workMinutes = Carbon::parse($attendance->finish_at)
                        ->diffInMinutes(Carbon::parse($attendance->start_at));
                }


                $formatMinutes = function ($minutes) {
                    $h = floor($minutes / 60);
                    $m = $minutes % 60;
                    return sprintf('%d:%02d', $h, $m);
                };

                $weekMap = ['日', '月', '火', '水', '木', '金', '土'];

                return [
                    'date' => Carbon::parse($attendance->start_at)->format('m/d')
                        . '(' . $weekMap[Carbon::parse($attendance->start_at)->dayOfWeek] . ')',
                    'start_at'    => Carbon::parse($attendance->start_at)->format('H:i'),
                    'finish_at'   => $attendance->finish_at ? Carbon::parse($attendance->finish_at)->format('H:i') : null,
                    'rest_at'     => $formatMinutes($restMinutes),
                    'total_at'    => $formatMinutes(max(0, $workMinutes - $restMinutes)),
                    'id' => $attendance->id,
                ];
            });


        return view('admin_attendance_list', compact('today', 'attendances'));
    }
}
