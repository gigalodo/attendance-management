<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Attendance;
use App\Models\Intermission;

use App\Http\Requests\AttendanceRequest;

use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class AttendanceController extends Controller
{

    public function attendance()
    {
        //状況に応じて表示を変える
        //
        // $req = Attendance::where('user_id', Auth::id())->where('start_at', date(""))->get();
        $todayAttendance = Attendance::where('user_id', Auth::id())
            ->where('is_request', false)
            ->whereDate('start_at', Carbon::today())
            // ->with('intermissions')
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
            ->with('intermissions')
            ->first();

        $error_messege = "";

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

                        // $intermissions = $todayAttendance->intermissions()->where('finish_at', null)->fist();
                        $intermissions = $todayAttendance->intermissions()->where('finish_at', null);
                        if ($intermissions->count() === 1) {
                            $todayAttendance->update(['status' => Attendance::STATUS_WORKING]);
                            $intermissions->first()->update(['finish_at' => Carbon::now(),]);
                        } else {
                            $error_messege = "休憩中のデータが複数存在します。「申請」から正しい情報を入力してください。";
                        }
                    } else {
                        $error_messege = "サーバーとページの情報が一致しません。再度ボタンを押してください。";
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

        if ($error_messege) {
            return back()->with($error_messege);
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

        // 勤怠データ取得（当月分のみ）
        $rawAttendances = Attendance::where('user_id', Auth::id())
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

        return view('attendance_list', compact('month', 'attendances'));
    }
    // {

    //     $month = $request->filled('month') //2025-09等の形式
    //         ? Carbon::parse($request->month)
    //         : Carbon::now();

    //     $attendances = Attendance::where('user_id', Auth::id())
    //         ->where('is_request', false)
    //         ->whereBetween('start_at', [
    //             Carbon::now()->startOfMonth(),
    //             Carbon::now()->endOfMonth()
    //         ])
    //         ->with('intermissions')
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


    //     return view('attendance_list', compact('month', 'attendances'));
    // }


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

        //値のチェック＋エラーの場合にエラーメッセージを返す
        //全部消して入れなおす！！！→必要なし　新規レコードの為  現在の休憩最大数＋１だと少ない場合がないか・・・？
        // $intermissions = Intermission::where('attendance_id', $attendance->id)->get();
        // foreach ($intermissions as $intermission) {
        //     $intermission->delete();
        // }
        // dd($request->comments);

        //備考カラム追加！！！comments　request->trueに！！ attendance_finish_at
        $attendance_record = Attendance::create([
            'user_id' => Auth::id(),
            // 'start_at' => Carbon::parse($attendance->start_at)->format('Y-m-d ') . $request->attendance_start_at . ":00",
            'start_at' =>  Carbon::parse($attendance->start_at)->setTimeFromTimeString($request->attendance_start_at),
            'finish_at' =>  Carbon::parse($attendance->start_at)->setTimeFromTimeString($request->attendance_finish_at),
            'status' => Attendance::STATUS_FINISHED,
            'is_request' => true,
            'is_approved' => false,
            'comments' => $request->comments,
        ]);

        // 開始・終了nullで入る場合がある・・・・？→バリデーションチェックでNG →NGはNG　4つの欄のうち２，３個使うって言うのは普通にあり得るため
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

        //GPT提案
        // collect($request->input('intermissions', []))
        //     ->filter(fn($i) => filled($i['start_at']) && filled($i['finish_at']))
        //     ->each(function ($i) use ($attendance_record, $attendance) {
        //         Intermission::create([
        //             'attendance_id' => $attendance_record->id,
        //             'start_at' => Carbon::parse($attendance->start_at)->setTimeFromTimeString($i['start_at']),
        //             'finish_at' => Carbon::parse($attendance->start_at)->setTimeFromTimeString($i['finish_at']),
        //         ]);
        //     });


        return redirect('/attendance/list');
    }
}
