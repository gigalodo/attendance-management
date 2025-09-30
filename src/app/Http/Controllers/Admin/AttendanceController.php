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

class AttendanceController extends Controller
{
    
    public function storeAttendanceEmpty(Request $request)
    {

        $date = Carbon::parse($request->start_at);

        $attendanceRequest = AttendanceRequest::createFrom($request);
        $attendanceRequest->setContainer(app())->setRedirector(app('redirect'));

        $validated = $attendanceRequest->validateResolved();

        //備考カラム追加！！！comments　request->trueに！！ attendance_finish_at
        // $attendance_record = Attendance::update([
        $attendance = Attendance::create([
            'user_id' => $request->id,

            'start_at' =>  Carbon::parse($attendance->start_at)->setTimeFromTimeString($request->attendance_start_at),
            'finish_at' =>  Carbon::parse($attendance->start_at)->setTimeFromTimeString($request->attendance_finish_at),
            'status' => Attendance::STATUS_FINISHED,
            'is_request' => false,
            'is_approved' => false,
            'comments' => $request->comments,
        ]);

        $intermissions = Intermission::where('attendance_id', $attendance->id)->get();

        foreach ($intermissions as $intermission) {
            $intermission->delete();
        }

        // 開始・終了nullで入る場合がある・・・・？→バリデーションチェックでNG →NGはNG　4つの欄のうち２，３個使うって言うのは普通にあり得るため
        if ($request->input('intermissions')) {
            foreach ($request->input('intermissions') as $intermission) {
                if ($intermission['start_at'] && $intermission['finish_at']) {
                    Intermission::create([
                        'attendance_id' => $attendance->id,
                        'start_at' =>  Carbon::parse($attendance->start_at)->setTimeFromTimeString($intermission['start_at']),
                        'finish_at' => Carbon::parse($attendance->start_at)->setTimeFromTimeString($intermission['finish_at']),
                    ]);
                }
            }
        }

        return redirect('/admin/attendance/list');
    }


    public function attendanceEmpty(Request $request)
    {



        if (!$request->user) {
            return back();
        }
        $user = User::find($request->user);


        if (!$user) {
            return back();
        }

        $date = $request->filled('date')
            ? Carbon::parse($request->date)
            : Carbon::now();

        $intermissions = [];

        $attendance = [
            'id' => null,
            'user_id' => $user->id,
            'name' => $user->name,
            // 'name' => Auth::user()->name,
            'year' => Carbon::parse($date)->format('Y年'),
            'date' => Carbon::parse($date)->format('n月j日'),
            'start_at'    => null,
            'finish_at'   => null,
            'comments' => null,
            'is_request' => false,
            'is_approved' => false,
        ];

        return view('attendance_detail', compact('attendance', 'intermissions'));
    }


    public function index(Request $request)
    {

        $date = $request->filled('date')
            ? Carbon::parse($request->date)
            : Carbon::now();

        // dd($date);
        //$request->date('date');より安全なのか？？？？？？？？？？？？
        // $date = $request->date ? $request->date : Carbon::now();

        $users = User::where('role', 'user')
            ->with(['attendances' => function ($query) use ($date) {
                $query->whereDate('start_at', $date)
                    ->where('is_request', false)
                    ->with('intermissions');
            }])
            ->get();

        $rows = $users->map(function ($user) {
            $attendance = $user->attendances->first();

            if (!$attendance) {
                return [
                    'name'   => $user->name,
                    'start'  => '',
                    'finish' => '',
                    'break'  => '',
                    'work'   => '',
                    'attendance' => null,
                    'id' => $user->id,
                ];
            }

            $start  = Carbon::parse($attendance->start_at)->format('H:i');
            $finish = $attendance->finish_at ? Carbon::parse($attendance->finish_at)->format('H:i') : '';

            $breakMinutes = $attendance->intermissions->sum(function ($intermission) {
                return $intermission->finish_at
                    ? Carbon::parse($intermission->start_at)->diffInMinutes(Carbon::parse($intermission->finish_at))
                    : 0;
            });
            $breakFormatted = $breakMinutes > 0
                ? sprintf('%d:%02d', intdiv($breakMinutes, 60), $breakMinutes % 60)
                : '';

            $workMinutes = $attendance->finish_at
                ? Carbon::parse($attendance->start_at)->diffInMinutes(Carbon::parse($attendance->finish_at)) - $breakMinutes
                : 0;
            $workFormatted = $workMinutes > 0
                ? sprintf('%d:%02d', intdiv($workMinutes, 60), $workMinutes % 60)
                : '';

            return [
                'name'   => $user->name,
                'start'  => $start,
                'finish' => $finish,
                'break'  => $breakFormatted,
                'work'   => $workFormatted,
                'attendance' => $attendance,
                'id' => $user->id,
            ];
        });

        $date = [
            'day' => $date->format('Y/m/d'),
            'before' => $date->copy()->subDay()->toDateString(),
            'after' => $date->copy()->addDay()->toDateString(),
            'today' => $date->toDateString(),
            'str' => $date->format('Y年n月j日'),
        ];
        // dd($date);
        return view('admin_attendance_list', compact('date', 'rows'));
    }


    public function staffIndex(User $user, Request $request)
    {
        // 月を取得（リクエスト or 現在）
        $month = $request->filled('month')
            ? Carbon::parse($request->month)
            : Carbon::now();

        $startOfMonth = $month->copy()->startOfMonth();
        $endOfMonth = $month->copy()->endOfMonth();

        // 勤怠データ取得（当月分のみ）
        $rawAttendances = Attendance::where('user_id', $user->id)
            ->where('is_request', false)
            ->whereBetween('start_at', [$startOfMonth, $endOfMonth])
            ->with('intermissions')
            ->get()
            ->keyBy(function ($attendance) {
                return Carbon::parse($attendance->start_at)->toDateString(); // "2025-09-01" の形式
            });

        // 曜日表示用
        $weekMap = ['日', '月', '火', '水', '木', '金', '土'];

        // 月の全日付ループ
        $attendances = [];
        for ($date = $startOfMonth->copy(); $date->lte($endOfMonth); $date->addDay()) {
            $dateStr = $date->toDateString();
            $attendance = $rawAttendances->get($dateStr);

            if ($attendance) {
                // 勤怠データがある場合
                $restMinutes = $attendance->intermissions->sum(function ($intermission) {
                    if ($intermission->finish_at) {
                        return Carbon::parse($intermission->finish_at)
                            ->diffInMinutes(Carbon::parse($intermission->start_at));
                    }
                    return 0;
                });

                $workMinutes = $attendance->finish_at
                    ? Carbon::parse($attendance->finish_at)->diffInMinutes(Carbon::parse($attendance->start_at))
                    : 0;

                $formatMinutes = function ($minutes) {
                    $h = floor($minutes / 60);
                    $m = $minutes % 60;
                    return sprintf('%d:%02d', $h, $m);
                };

                $attendances[] = [
                    'date' => $date->format('m/d') . '(' . $weekMap[$date->dayOfWeek] . ')',
                    'start_at' => Carbon::parse($attendance->start_at)->format('H:i'),
                    'finish_at' => $attendance->finish_at ? Carbon::parse($attendance->finish_at)->format('H:i') : '',
                    'rest_at' => $formatMinutes($restMinutes),
                    'total_at' => $formatMinutes(max(0, $workMinutes - $restMinutes)),
                    'id' => $attendance->id,
                ];
            } else {
                // 勤怠データがない場合（空白）
                $attendances[] = [
                    'date' => $date->format('m/d') . '(' . $weekMap[$date->dayOfWeek] . ')',
                    'start_at' => '',
                    'finish_at' => '',
                    'rest_at' => '',
                    'total_at' => '',
                    'id' => null,
                ];
            }
        }


        $month = [
            'day' => $startOfMonth->format('Y-n'),
            'before' => $startOfMonth->copy()->subMonth()->format('Y-n'),
            'after' => $startOfMonth->copy()->addMonth()->format('Y-n'),
            'str' => $startOfMonth->format('Y/n'),
        ];

        // return view('attendance_list', compact('month', 'attendances'));
        return view('admin_attendance_staff', compact('user', 'month', 'attendances'));
    }


    public function attendanceDetail(Attendance $attendance)
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

        //備考カラム追加！！！comments　request->trueに！！ attendance_finish_at
        // $attendance_record = Attendance::update([
        $attendance->update([
            'user_id' => $attendance->user->id,
            // 'start_at' => Carbon::parse($attendance->start_at)->format('Y-m-d ') . $request->attendance_start_at . ":00",
            'start_at' =>  Carbon::parse($attendance->start_at)->setTimeFromTimeString($request->attendance_start_at),
            'finish_at' =>  Carbon::parse($attendance->start_at)->setTimeFromTimeString($request->attendance_finish_at),
            'status' => Attendance::STATUS_FINISHED,
            'is_request' => false,
            'is_approved' => false,
            'comments' => $request->comments,
        ]);

        $intermissions = Intermission::where('attendance_id', $attendance->id)->get();

        foreach ($intermissions as $intermission) {
            $intermission->delete();
        }

        // 開始・終了nullで入る場合がある・・・・？→バリデーションチェックでNG →NGはNG　4つの欄のうち２，３個使うって言うのは普通にあり得るため
        if ($request->input('intermissions')) {
            foreach ($request->input('intermissions') as $intermission) {
                if ($intermission['start_at'] && $intermission['finish_at']) {
                    Intermission::create([
                        'attendance_id' => $attendance->id,
                        'start_at' =>  Carbon::parse($attendance->start_at)->setTimeFromTimeString($intermission['start_at']),
                        'finish_at' => Carbon::parse($attendance->start_at)->setTimeFromTimeString($intermission['finish_at']),
                    ]);
                }
            }
        }

        return redirect('/admin/attendance/list');
    }
}
