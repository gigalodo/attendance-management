<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Attendance;
use App\Models\Intermission;

use App\Http\Requests\AttendanceRequest;

use Carbon\Carbon;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AttendanceController extends Controller
{

    public function export(Request $request)
    {
        $month = Carbon::parse($request->month);

        //kokokara

        $startOfMonth = $month->copy()->startOfMonth();
        $endOfMonth = $month->copy()->endOfMonth();


        $rawAttendances = Attendance::where('user_id', $request->user_id)
            ->where('is_request', false)
            ->whereBetween('start_at', [$startOfMonth, $endOfMonth])
            ->with(['intermissions', 'user'])
            ->get()
            ->keyBy(function ($attendance) {
                return Carbon::parse($attendance->start_at)->toDateString(); // "2025-09-01" の形式
            });

        $weekMap = ['日', '月', '火', '水', '木', '金', '土'];

        $csvData = [];
        for ($date = $startOfMonth->copy(); $date->lte($endOfMonth); $date->addDay()) {
            $dateStr = $date->toDateString();
            $attendance = $rawAttendances->get($dateStr);

            if ($attendance) {
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

                $csvData[] = [
                    'date' => $date->format('m/d') . '(' . $weekMap[$date->dayOfWeek] . ')',
                    'start_at' => Carbon::parse($attendance->start_at)->format('H:i'),
                    'finish_at' => $attendance->finish_at ? Carbon::parse($attendance->finish_at)->format('H:i') : '',
                    'rest_at' => $formatMinutes($restMinutes),
                    'total_at' => $formatMinutes(max(0, $workMinutes - $restMinutes)),
                ];
            } else {
                $csvData[] = [
                    'date' => $date->format('m/d') . '(' . $weekMap[$date->dayOfWeek] . ')',
                    'start_at' => '',
                    'finish_at' => '',
                    'rest_at' => '',
                    'total_at' => '',
                ];
            }
        }

        //kokomade
        $csvHeader = [
            '日付',
            '出勤',
            '退勤',
            '休憩',
            '合計',
        ];

        $response = new StreamedResponse(function () use ($csvHeader, $csvData) {
            $createCsvFile = fopen('php://output', 'w');

            mb_convert_variables('SJIS-win', 'UTF-8', $csvHeader);

            fputcsv($createCsvFile, $csvHeader);

            foreach ($csvData as $csv) {
                mb_convert_variables('SJIS-win', 'UTF-8', $csv);
                fputcsv($createCsvFile, $csv);
            }

            fclose($createCsvFile);
        }, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename=ID' . $request->user_id . "_" . $request->month . ".csv",
        ]);

        return $response;
    }

    public function storeAttendanceEmpty(Request $request)
    {
        $attendanceRequest = AttendanceRequest::createFrom($request);
        $attendanceRequest->setContainer(app())->setRedirector(app('redirect'));

        $validated = $attendanceRequest->validateResolved();

        $date = Carbon::parse($request->date);

        $attendance = Attendance::create([
            'user_id' => $request->user_id,
            'start_at' =>  $date->copy()->setTimeFromTimeString($request->attendance_start_at),
            'finish_at' =>  $date->copy()->setTimeFromTimeString($request->attendance_finish_at),
            'status' => Attendance::STATUS_FINISHED,
            'is_request' => false,
            'is_approved' => false,
            'comments' => $request->comments,
        ]);

        if ($request->input('intermissions')) {
            foreach ($request->input('intermissions') as $intermission) {
                if ($intermission['start_at'] && $intermission['finish_at']) {
                    Intermission::create([
                        'attendance_id' => $attendance->id,
                        'start_at' =>  $date->copy()->setTimeFromTimeString($intermission['start_at']),
                        'finish_at' => $date->copy()->setTimeFromTimeString($intermission['finish_at']),
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

    public function index(Request $request)
    {
        $date = $request->filled('date')
            ? Carbon::parse($request->date)
            : Carbon::now();

        $users = User::where('role', 'user')
            ->with(['attendances' => function ($query) use ($date) {
                $query->whereDate('start_at', $date)
                    ->where('is_request', false)
                    ->with(['intermissions', 'user']);
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

        return view('admin_attendance_list', compact('date', 'rows'));
    }

    public function staffIndex(User $user, Request $request)
    {
        $month = $request->filled('month')
            ? Carbon::parse($request->month)
            : Carbon::now();

        $startOfMonth = $month->copy()->startOfMonth();
        $endOfMonth = $month->copy()->endOfMonth();

        $rawAttendances = Attendance::where('user_id', $user->id)
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
            $attendance = $rawAttendances->get($dateStr);

            if ($attendance) {
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

                $rows[] = [
                    'date' => $date->format('m/d') . '(' . $weekMap[$date->dayOfWeek] . ')',
                    'start_at' => Carbon::parse($attendance->start_at)->format('H:i'),
                    'finish_at' => $attendance->finish_at ? Carbon::parse($attendance->finish_at)->format('H:i') : '',
                    'rest_at' => $formatMinutes($restMinutes),
                    'total_at' => $formatMinutes(max(0, $workMinutes - $restMinutes)),
                    'id' => $attendance->id,
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
            'this_month' => $startOfMonth->format('Y-n'),
            'before' => $startOfMonth->copy()->subMonth()->format('Y-n'),
            'after' => $startOfMonth->copy()->addMonth()->format('Y-n'),
            'str' => $startOfMonth->format('Y/n'),
        ];

        return view('admin_attendance_staff', compact('user', 'month', 'rows'));
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

        $attendance->update([
            'user_id' => $attendance->user->id,
            'start_at' =>  Carbon::parse($attendance->start_at)->setTimeFromTimeString($request->attendance_start_at),
            'finish_at' =>  Carbon::parse($attendance->start_at)->setTimeFromTimeString($request->attendance_finish_at),
            'status' => Attendance::STATUS_FINISHED,
            'is_request' => false,
            'is_approved' => false,
            'comments' => $request->comments,
        ]);

        Intermission::where('attendance_id', $attendance->id)->delete();

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
