<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Attendance;
use App\Models\Intermission;
use Carbon\Carbon;

class AttendancesTableSeeder extends Seeder
{
    public function run()
    {
        $user = User::where('role', 'user')->first();

        $dates = [
            '2025-09-01',
            '2025-09-15',
            '2025-09-30',
            '2025-10-01',
            '2025-10-10',
        ];

        foreach ($dates as $date) {
            $start = Carbon::parse($date . ' 09:00:00');
            $finish = Carbon::parse($date . ' 18:00:00');

            $attendance = Attendance::create([
                'user_id' => $user->id,
                'start_at' => $start,
                'finish_at' => $finish,
                'status' => Attendance::STATUS_FINISHED,
                'is_request' => false,
                'is_approved' => false,
                'comments' => 'ダミー勤怠データ',
            ]);

            Intermission::create([
                'attendance_id' => $attendance->id,
                'start_at' => $start->copy()->addHours(3),
                'finish_at' => $start->copy()->addHours(3)->addMinutes(30),
            ]);
        }
    }
}
