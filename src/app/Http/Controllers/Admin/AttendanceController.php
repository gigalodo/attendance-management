<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Attendance;
use App\Models\Intermission;
use App\Http\Requests\AttendanceRequest;
use App\Services\AttendanceFormatter;
use Carbon\Carbon;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AttendanceController extends Controller
{
    public function export(Request $request)
    {
        $month = Carbon::parse($request->month);
        $startOfMonth = $month->copy()->startOfMonth();
        $endOfMonth = $month->copy()->endOfMonth();

        $rawAttendances = Attendance::where('user_id', $request->user_id)
            ->where('is_request', false)
            ->whereBetween('start_at', [$startOfMonth, $endOfMonth])
            ->with(['intermissions', 'user'])
            ->get()
            ->keyBy(fn($attendance) => Carbon::parse($attendance->start_at)->toDateString());

        $csvData = [];
        for ($date = $startOfMonth->copy(); $date->lte($endOfMonth); $date->addDay()) {
            $dateStr = $date->toDateString();
            $attendance = $rawAttendances->get($dateStr);
            $csvData[] = AttendanceFormatter::buildAttendanceRow($attendance, $date);
        }

        $csvHeader = ['日付', '出勤', '退勤', '休憩', '合計'];

        $response = new StreamedResponse(function () use ($csvHeader, $csvData, $request) {
            $fp = fopen('php://output', 'w');
            mb_convert_variables('SJIS-win', 'UTF-8', $csvHeader);
            fputcsv($fp, $csvHeader);
            foreach ($csvData as $csvRow) {
                $values = [
                    $csvRow['date'] ?? '',
                    $csvRow['start_at'] ?? '',
                    $csvRow['finish_at'] ?? '',
                    $csvRow['rest_at'] ?? '',
                    $csvRow['total_at'] ?? '',
                ];

                mb_convert_variables('SJIS-win', 'UTF-8', $values);
                fputcsv($fp, $values);
            }
            fclose($fp);
        }, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename=ID' . $request->user_id . '_' . $request->month . '.csv',
        ]);

        return $response;
    }

    public function storeAttendanceEmpty(Request $request)
    {
        $attendanceRequest = AttendanceRequest::createFrom($request);
        $attendanceRequest->setContainer(app())->setRedirector(app('redirect'));
        $attendanceRequest->validateResolved();

        $date = Carbon::parse($request->date);

        $attendance = Attendance::create([
            'user_id' => $request->user_id,
            'start_at' => $date->copy()->setTimeFromTimeString($request->attendance_start_at),
            'finish_at' => $date->copy()->setTimeFromTimeString($request->attendance_finish_at),
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
                        'start_at' => $date->copy()->setTimeFromTimeString($intermission['start_at']),
                        'finish_at' => $date->copy()->setTimeFromTimeString($intermission['finish_at']),
                    ]);
                }
            }
        }

        return redirect('/admin/attendance/list');
    }

    public function attendanceEmpty(Request $request)
    {
        if (!$request->user) return back();
        $user = User::find($request->user);
        if (!$user) return back();

        $date = $request->filled('date') ? Carbon::parse($request->date) : Carbon::now();
        $intermissions = [];

        $attendance = [
            'id' => null,
            'user_id' => $user->id,
            'name' => $user->name,
            'year' => $date->format('Y年'),
            'date' => $date->format('n月j日'),
            'start_at' => null,
            'finish_at' => null,
            'comments' => null,
            'is_request' => false,
            'is_approved' => false,
        ];

        return view('attendance_detail', compact('attendance', 'intermissions', 'date'));
    }

    public function index(Request $request)
    {
        $date = $request->filled('date') ? Carbon::parse($request->date) : Carbon::now();

        $users = User::where('role', 'user')
            ->with(['attendances' => fn($q) => $q->whereDate('start_at', $date)->where('is_request', false)->with(['intermissions', 'user'])])
            ->get();

        $rows = $users->map(fn($user) => AttendanceFormatter::buildAttendanceListRow($user))->toArray();

        $dateArr = [
            'day' => $date->format('Y/m/d'),
            'before' => $date->copy()->subDay()->toDateString(),
            'after' => $date->copy()->addDay()->toDateString(),
            'today' => $date->toDateString(),
            'str' => $date->format('Y年n月j日'),
        ];

        return view('admin_attendance_list', ['date' => $dateArr, 'rows' => $rows]);
    }

    public function staffIndex(User $user, Request $request)
    {
        $month = $request->filled('month') ? Carbon::parse($request->month) : Carbon::now();
        $startOfMonth = $month->copy()->startOfMonth();
        $endOfMonth = $month->copy()->endOfMonth();

        $rawAttendances = Attendance::where('user_id', $user->id)
            ->where('is_request', false)
            ->whereBetween('start_at', [$startOfMonth, $endOfMonth])
            ->with(['intermissions', 'user'])
            ->get()
            ->keyBy(fn($att) => Carbon::parse($att->start_at)->toDateString());

        $rows = [];
        for ($date = $startOfMonth->copy(); $date <= $endOfMonth; $date->addDay()) {
            $attendance = $rawAttendances->get($date->toDateString());
            $rows[] = AttendanceFormatter::buildAttendanceRow($attendance, $date);
        }

        $monthArr = [
            'this_month' => $startOfMonth->format('Y-n'),
            'before' => $startOfMonth->copy()->subMonth()->format('Y-n'),
            'after' => $startOfMonth->copy()->addMonth()->format('Y-n'),
            'str' => $startOfMonth->format('Y/n'),
        ];

        return view('admin_attendance_staff', ['user' => $user, 'month' => $monthArr, 'rows' => $rows]);
    }

    public function attendanceDetail(Attendance $attendance)
    {
        $attendance->load(['intermissions', 'user']);
        $intermissions = AttendanceFormatter::formatIntermissions($attendance->intermissions->toArray());
        $attendanceArr = AttendanceFormatter::formatAttendanceDetail($attendance);

        return view('attendance_detail', ['attendance' => $attendanceArr, 'intermissions' => $intermissions]);
    }

    public function storeAttendanceDetail(Attendance $attendance, Request $request)
    {
        $attendanceRequest = AttendanceRequest::createFrom($request);
        $attendanceRequest->setContainer(app())->setRedirector(app('redirect'));
        $attendanceRequest->validateResolved();

        $attendance->update([
            'user_id' => $attendance->user->id,
            'start_at' => Carbon::parse($attendance->start_at)->setTimeFromTimeString($request->attendance_start_at),
            'finish_at' => Carbon::parse($attendance->start_at)->setTimeFromTimeString($request->attendance_finish_at),
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
                        'start_at' => Carbon::parse($attendance->start_at)->setTimeFromTimeString($intermission['start_at']),
                        'finish_at' => Carbon::parse($attendance->start_at)->setTimeFromTimeString($intermission['finish_at']),
                    ]);
                }
            }
        }

        return redirect('/admin/attendance/list');
    }
}
