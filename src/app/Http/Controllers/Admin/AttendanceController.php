<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Attendance;
use App\Models\Intermission;

use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class AttendanceController extends Controller
{

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
            ];
        });

        $date = [
            'day' => $date->toDateString(),
            'before' => $date->copy()->subDay()->toDateString(),
            'after' => $date->copy()->addDay()->toDateString(),
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
}
