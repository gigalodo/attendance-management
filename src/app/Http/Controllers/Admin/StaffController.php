<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Attendance;
use App\Models\Intermission;

use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class StaffController extends Controller
{

    public function index()
    {


        $users = User::where('role', 'user')->get();
        // return view('admin_attendance_staff');
        return view('admin_staff_list', compact('users'));
    }
}


// {
//     $date = Carbon::now();

//     $users = User::where('role', '!=', 'admin')
//         ->with(['attendances' => function ($query) use ($date) {
//             $query->whereDate('start_at', $date)
//                 ->with('intermissions');
//         }])
//         ->get();

//     $rows = $users->map(function ($user) {
//         $attendance = $user->attendances->first(); // 今日1勤務だけ想定

//         if (!$attendance) {
//             return [
//                 'name'   => $user->name,
//                 'start'  => '',
//                 'finish' => '',
//                 'break'  => '',
//                 'work'   => '',
//                 'attendance' => null,
//             ];
//         }

//         $start  = Carbon::parse($attendance->start_at)->format('H:i');
//         $finish = $attendance->finish_at ? Carbon::parse($attendance->finish_at)->format('H:i') : '';

//         $breakMinutes = $attendance->intermissions->sum(function ($intermission) {
//             return $intermission->finish_at
//                 ? Carbon::parse($intermission->start_at)->diffInMinutes(Carbon::parse($intermission->finish_at))
//                 : 0;
//         });
//         $breakFormatted = $breakMinutes > 0
//             ? sprintf('%d:%02d', intdiv($breakMinutes, 60), $breakMinutes % 60)
//             : '';

//         $workMinutes = $attendance->finish_at
//             ? Carbon::parse($attendance->start_at)->diffInMinutes(Carbon::parse($attendance->finish_at)) - $breakMinutes
//             : 0;
//         $workFormatted = $workMinutes > 0
//             ? sprintf('%d:%02d', intdiv($workMinutes, 60), $workMinutes % 60)
//             : '';

//         return [
//             'name'   => $user->name,
//             'start'  => $start,
//             'finish' => $finish,
//             'break'  => $breakFormatted,
//             'work'   => $workFormatted,
//             'attendance' => $attendance,
//         ];
//     });

//     return view('attendance.index', compact('rows'));
// }