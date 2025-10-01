<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Attendance;
use App\Models\Intermission;

use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class StampCorrectionRequestController extends Controller
{

    public function requestList()
    {
        $requestAttendances = Attendance::where('user_id', Auth::id())
            ->where('is_request', true)
            ->where('is_approved', false)
            ->with(['intermissions', 'user'])
            ->get();

        $approvedAttendances = Attendance::where('user_id', Auth::id())
            ->where('is_request', true)
            ->where('is_approved', true)
            ->with(['intermissions', 'user'])
            ->get();

        return view('stamp_request_list', compact('requestAttendances', 'approvedAttendances'));
    }

    // public function storeAttendance(Request $request)
    // {
    //     $todayAttendance = Attendance::where('user_id', Auth::id())
    //         ->where('is_request', false)
    //         ->whereDate('start_at', Carbon::today())
    //         ->with(['intermissions', 'user'])
    //         ->first();

    //     $error_message = "";

    //     if ($todayAttendance) {
    //         switch ($todayAttendance->status) {
    //             case Attendance::STATUS_WORKING:
    //                 if ($request->input('button_type') === 'button1') {
    //                     $todayAttendance->update([
    //                         'status' => Attendance::STATUS_FINISHED,
    //                         'finish_at' => Carbon::now()
    //                     ]);
    //                 } elseif ($request->input('button_type') === 'button2') {
    //                     $todayAttendance->update(['status' => Attendance::STATUS_RESTING]);
    //                     Intermission::create([
    //                         'attendance_id' => $todayAttendance->id,
    //                         'start_at' => Carbon::now(),
    //                         'finish_at' => null,
    //                     ]);
    //                 }
    //                 break;

    //             case Attendance::STATUS_RESTING:
    //                 if ($request->input('button_type') === 'button2') {
    //                     $intermissions = $todayAttendance->intermissions()->where('finish_at', null);
    //                     if ($intermissions->count() === 1) {
    //                         $todayAttendance->update(['status' => Attendance::STATUS_WORKING]);
    //                         $intermissions->first()->update(['finish_at' => Carbon::now(),]);
    //                     } else {
    //                         $error_message = "休憩中のデータが複数存在します。「申請」から正しい情報を入力してください。";
    //                     }
    //                 } else {
    //                     $error_message = "サーバーとページの情報が一致しません。再度ボタンを押してください。";
    //                 }
    //                 break;
    //         }
    //     } else {
    //         Attendance::create([
    //             'user_id' => Auth::id(),
    //             'start_at' => Carbon::now(),
    //             'finish_at' => null,
    //             'status' => Attendance::STATUS_WORKING,
    //             'is_request' => false,
    //             'is_approved' => false,
    //         ]);
    //     }

    //     if ($error_message) {
    //         return back()->with($error_message);
    //     } else {
    //         return redirect('/attendance');
    //     }
    // }


    // public function index()
    // {
    //     $today = date("m"); //使用？？？

    //     $attendances = Attendance::where('user_id', Auth::id())
    //         ->where('is_request', false)
    //         ->whereBetween('start_at', [
    //             Carbon::now()->startOfMonth(),
    //             Carbon::now()->endOfMonth()
    //         ])
    //         ->with(['intermissions', 'user'])
    //         ->get()
    //         ->map(function ($attendance) {

    //             $restMinutes = $attendance->intermissions->sum(function ($intermission) {
    //                 if ($intermission->finish_at) {
    //                     return Carbon::parse($intermission->finish_at)
    //                         ->diffInMinutes(Carbon::parse($intermission->start_at));
    //                 }
    //                 return 0;
    //             });

    //             $workMinutes = 0;
    //             if ($attendance->finish_at) {
    //                 $workMinutes = Carbon::parse($attendance->finish_at)
    //                     ->diffInMinutes(Carbon::parse($attendance->start_at));
    //             }

    //             $formatMinutes = function ($minutes) {
    //                 $h = floor($minutes / 60);
    //                 $m = $minutes % 60;
    //                 return sprintf('%d:%02d', $h, $m);
    //             };

    //             $weekMap = ['日', '月', '火', '水', '木', '金', '土'];

    //             return [
    //                 'date' => Carbon::parse($attendance->start_at)->format('m/d')
    //                     . '(' . $weekMap[Carbon::parse($attendance->start_at)->dayOfWeek] . ')',
    //                 'start_at'    => Carbon::parse($attendance->start_at)->format('H:i'),
    //                 'finish_at'   => $attendance->finish_at ? Carbon::parse($attendance->finish_at)->format('H:i') : null,
    //                 'rest_at'     => $formatMinutes($restMinutes),
    //                 'total_at'    => $formatMinutes(max(0, $workMinutes - $restMinutes)),
    //                 'id' => $attendance->id,
    //             ];
    //         });

    //     return view('attendance_list', compact('today', 'attendances'));
    // }
}
