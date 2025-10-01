<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Attendance;
use App\Models\Intermission;
use App\Models\User;

use App\Http\Requests\AttendanceRequest;

use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class AttendanceController extends Controller
{

    public function storeAttendanceEmpty(Request $request)
    {
        $attendanceRequest = AttendanceRequest::createFrom($request);
        $attendanceRequest->setContainer(app())->setRedirector(app('redirect'));

        $validated = $attendanceRequest->validateResolved();

        $date = Carbon::parse($request->date);

        $attendance_record = Attendance::create([
            'user_id' => Auth::id(),
            'start_at' =>  $date->copy()->setTimeFromTimeString($request->attendance_start_at),
            'finish_at' =>  $date->copy()->setTimeFromTimeString($request->attendance_finish_at),
            'status' => Attendance::STATUS_FINISHED,
            'is_request' => true,
            'is_approved' => false,
            'comments' => $request->comments,
        ]);

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

        return redirect('/attendance/list');
    }

    public function attendanceEmpty(Request $request)
    {
        $user = Auth::user();

        $date = $request->filled('date')
            ? Carbon::parse($request->date)
            : Carbon::now();

        $intermissions = [];

        $attendance = [
            'id' => null,
            'user_id' => $user->id,
            'name' => $user->name,
            'year' => $date->format('Y年'),
            'date' => $date->format('n月j日'),
            'start_at'    => null,
            'finish_at'   => null,
            'comments' => null,
            'is_request' => false,
            'is_approved' => false,
        ];

        return view('attendance_detail', compact('attendance', 'intermissions', 'date'));
    }

    public function attendance()
    {
        $todayAttendance = Attendance::where('user_id', Auth::id())
            ->where('is_request', false)
            ->whereDate('start_at', Carbon::today())
            ->first();

        $todayState = Attendance::STATUS_BEFORE_WORK;
        if ($todayAttendance) {
            $todayState = $todayAttendance->status;
        }

        return view('attendance_register', compact('todayState'));
    }

    public function storeAttendance(Request $request)
    {
        $todayAttendance = Attendance::where('user_id', Auth::id())
            ->where('is_request', false)
            ->whereDate('start_at', Carbon::today())
            ->with(['intermissions', 'user'])
            ->first();

        $error_message = "";

        if ($todayAttendance) {
            switch ($todayAttendance->status) {
                case Attendance::STATUS_WORKING:
                    if ($request->input('button_type') === 'button1') {
                        $todayAttendance->update([
                            'status' => Attendance::STATUS_FINISHED,
                            'finish_at' => Carbon::now()
                        ]);
                    } elseif ($request->input('button_type') === 'button2') {
                        $todayAttendance->update(['status' => Attendance::STATUS_RESTING]);
                        Intermission::create([
                            'attendance_id' => $todayAttendance->id,
                            'start_at' => Carbon::now(),
                            'finish_at' => null,
                        ]);
                    }
                    break;

                case Attendance::STATUS_RESTING:
                    if ($request->input('button_type') === 'button2') {
                        $intermissions = $todayAttendance->intermissions()->where('finish_at', null);
                        if ($intermissions->count() === 1) {
                            $todayAttendance->update(['status' => Attendance::STATUS_WORKING]);
                            $intermissions->first()->update(['finish_at' => Carbon::now(),]);
                        } else {
                            $error_message = "休憩中のデータが複数存在します。「申請」から正しい情報を入力してください。";
                        }
                    } else {
                        $error_message = "サーバーとページの情報が一致しません。再度ボタンを押してください。";
                    }
                    break;
            }
        } else {
            Attendance::create([
                'user_id' => Auth::id(),
                'start_at' => Carbon::now(),
                'finish_at' => null,
                'status' => Attendance::STATUS_WORKING,
                'is_request' => false,
                'is_approved' => false,
            ]);
        }

        if ($error_message) {
            return back()->with($error_message);
        } else {
            return redirect('/attendance');
        }
    }

    public function index(Request $request)
    {
        $month = $request->filled('month')
            ? Carbon::parse($request->month)
            : Carbon::now();

        $startOfMonth = $month->copy()->startOfMonth();
        $endOfMonth = $month->copy()->endOfMonth();

        $rawAttendances = Attendance::where('user_id', Auth::id())
            ->where('is_request', false)
            ->whereBetween('start_at', [$startOfMonth, $endOfMonth])
            ->with(['intermissions', 'user'])
            ->get()
            ->keyBy(function ($attendance) {
                return Carbon::parse($attendance->start_at)->toDateString();
            });

        $weekMap = ['日', '月', '火', '水', '木', '金', '土'];

        $rows = [];
        for ($date = $startOfMonth->copy(); $date->lte($endOfMonth); $date->addDay()) {
            $dateStr = $date->toDateString();
            $row = $rawAttendances->get($dateStr);

            if ($row) {
                $restMinutes = $row->intermissions->sum(function ($intermission) {
                    if ($intermission->finish_at) {
                        return Carbon::parse($intermission->finish_at)
                            ->diffInMinutes(Carbon::parse($intermission->start_at));
                    }
                    return 0;
                });

                $workMinutes = $row->finish_at
                    ? Carbon::parse($row->finish_at)->diffInMinutes(Carbon::parse($row->start_at))
                    : 0;

                $formatMinutes = function ($minutes) {
                    $h = floor($minutes / 60);
                    $m = $minutes % 60;
                    return sprintf('%d:%02d', $h, $m);
                };

                $rows[] = [
                    'date' => $date->format('m/d') . '(' . $weekMap[$date->dayOfWeek] . ')',
                    'start_at' => Carbon::parse($row->start_at)->format('H:i'),
                    'finish_at' => $row->finish_at ? Carbon::parse($row->finish_at)->format('H:i') : '',
                    'rest_at' => $formatMinutes($restMinutes),
                    'total_at' => $formatMinutes(max(0, $workMinutes - $restMinutes)),
                    'id' => $row->id,
                    'today' => null,
                ];
            } else {
                $rows[] = [
                    'date' => $date->format('m/d') . '(' . $weekMap[$date->dayOfWeek] . ')',
                    'start_at' => '',
                    'finish_at' => '',
                    'rest_at' => '',
                    'total_at' => '',
                    'id' => null,
                    'today' => $date->toDateString(),
                ];
            }
        }

        $month = [
            'day' => $startOfMonth->format('Y-n'),
            'before' => $startOfMonth->copy()->subMonth()->format('Y-n'),
            'after' => $startOfMonth->copy()->addMonth()->format('Y-n'),
            'str' => $startOfMonth->format('Y/n'),
        ];

        return view('attendance_list', compact('month', 'rows'));
    }

    public function attendanceDetail(Attendance $attendance)
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

        $attendance = [
            'id' => $attendance->id,
            'name' => $attendance->user->name,
            'year' => Carbon::parse($attendance->start_at)->format('Y年'),
            'date' => Carbon::parse($attendance->start_at)->format('n月j日'),
            'start_at'    => Carbon::parse($attendance->start_at)->format('H:i'),
            'finish_at'   => $attendance->finish_at ? Carbon::parse($attendance->finish_at)->format('H:i') : null,
            'comments' => $attendance->comments,
            'is_request' => $attendance->is_request,
            'is_approved' => $attendance->is_approved,
        ];

        return view('attendance_detail', compact('attendance', 'intermissions'));
    }

    public function storeAttendanceDetail(Attendance $attendance, Request $request)
    {
        $attendanceRequest = AttendanceRequest::createFrom($request);
        $attendanceRequest->setContainer(app())->setRedirector(app('redirect'));

        $validated = $attendanceRequest->validateResolved();

        $attendance_record = Attendance::create([
            'user_id' => Auth::id(),
            'start_at' =>  Carbon::parse($attendance->start_at)->setTimeFromTimeString($request->attendance_start_at),
            'finish_at' =>  Carbon::parse($attendance->start_at)->setTimeFromTimeString($request->attendance_finish_at),
            'status' => Attendance::STATUS_FINISHED,
            'is_request' => true,
            'is_approved' => false,
            'comments' => $request->comments,
        ]);

        if ($request->input('intermissions')) {
            foreach ($request->input('intermissions') as $intermission) {
                if ($intermission['start_at'] && $intermission['finish_at']) {
                    Intermission::create([
                        'attendance_id' => $attendance_record->id,
                        'start_at' =>  Carbon::parse($attendance->start_at)->setTimeFromTimeString($intermission['start_at']),
                        'finish_at' => Carbon::parse($attendance->start_at)->setTimeFromTimeString($intermission['finish_at']),
                    ]);
                }
            }
        }

        return redirect('/attendance/list');
    }
}
