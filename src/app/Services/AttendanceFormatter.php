<?php

namespace App\Services;

use App\Models\Attendance;
use Carbon\Carbon;

class AttendanceFormatter
{
    public static function getWeekMap(): array
    {
        return ['日', '月', '火', '水', '木', '金', '土'];
    }

    public static function formatAttendanceDetail(Attendance $attendance): array
    {
        return [
            'id' => $attendance->id,
            'name' => $attendance->user->name,
            'year' => Carbon::parse($attendance->start_at)->format('Y年'),
            'date' => Carbon::parse($attendance->start_at)->format('n月j日'),
            'start_at' => Carbon::parse($attendance->start_at)->format('H:i'),
            'finish_at' => $attendance->finish_at ? Carbon::parse($attendance->finish_at)->format('H:i') : null,
            'comments' => $attendance->comments,
            'is_request' => $attendance->is_request,
            'is_approved' => $attendance->is_approved,
        ];
    }

    public static function formatIntermissions(array $intermissions): array
    {
        return collect($intermissions)->map(fn($i) => [
            'start_at' => Carbon::parse($i['start_at'])->format('H:i'),
            'finish_at' => $i['finish_at'] ? Carbon::parse($i['finish_at'])->format('H:i') : null,
        ])->toArray();
    }

    public static function calculateMinutes(array $intermissions): int
    {
        return collect($intermissions)->sum(
            fn($i) => $i['finish_at'] ? Carbon::parse($i['finish_at'])->diffInMinutes(Carbon::parse($i['start_at'])) : 0
        );
    }

    public static function formatMinutes(int $minutes): string
    {
        return sprintf('%d:%02d', intdiv($minutes, 60), $minutes % 60);
    }

    public static function buildAttendanceRow($attendance, Carbon $date): array
    {
        $weekMap = self::getWeekMap();

        if ($attendance) {
            $intermissions = $attendance->intermissions->toArray();
            $restMinutes = self::calculateMinutes($intermissions);
            $workMinutes = $attendance->finish_at
                ? Carbon::parse($attendance->finish_at)->diffInMinutes(Carbon::parse($attendance->start_at))
                : 0;

            return [
                'date' => $date->format('m/d') . '(' . $weekMap[$date->dayOfWeek] . ')',
                'start_at' => Carbon::parse($attendance->start_at)->format('H:i'),
                'finish_at' => $attendance->finish_at ? Carbon::parse($attendance->finish_at)->format('H:i') : '',
                'rest_at' => self::formatMinutes($restMinutes),
                'total_at' => self::formatMinutes(max(0, $workMinutes - $restMinutes)),
                'id' => $attendance->id,
                'today' => null,
            ];
        } else {
            return [
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

    public static function buildAttendanceListRow($user): array
    {
        $attendance = $user->attendances->first();
        if (!$attendance) {
            return [
                'name' => $user->name,
                'start' => '',
                'finish' => '',
                'break' => '',
                'work' => '',
                'attendance' => null,
                'id' => $user->id,
            ];
        }

        $breakMinutes = $attendance->intermissions->sum(
            fn($i) => $i->finish_at ? Carbon::parse($i->start_at)->diffInMinutes(Carbon::parse($i->finish_at)) : 0
        );

        $workMinutes = $attendance->finish_at
            ? Carbon::parse($attendance->start_at)->diffInMinutes(Carbon::parse($attendance->finish_at)) - $breakMinutes
            : 0;

        return [
            'name' => $user->name,
            'start' => Carbon::parse($attendance->start_at)->format('H:i'),
            'finish' => $attendance->finish_at ? Carbon::parse($attendance->finish_at)->format('H:i') : '',
            'break' => $breakMinutes > 0 ? sprintf('%d:%02d', intdiv($breakMinutes, 60), $breakMinutes % 60) : '',
            'work' => $workMinutes > 0 ? sprintf('%d:%02d', intdiv($workMinutes, 60), $workMinutes % 60) : '',
            'attendance' => $attendance,
            'id' => $user->id,
        ];
    }

    public static function formatAttendanceForStamp(Attendance $attendance): array
    {
        $date = Carbon::parse($attendance->start_at);

        return [
            'id' => $attendance->id,
            'name' => $attendance->user->name,
            'year' => $date->format('Y年'),
            'date' => $date->format('n月j日'),
            'start_at' => $date->format('H:i'),
            'finish_at' => $attendance->finish_at ? Carbon::parse($attendance->finish_at)->format('H:i') : null,
            'comments' => $attendance->comments,
            'is_approved' => $attendance->is_approved,
        ];
    }

    public static function formatIntermissionsForStamp(array $intermissions): array
    {
        return collect($intermissions)->map(fn($i) => [
            'start_at' => Carbon::parse($i['start_at'])->format('H:i'),
            'finish_at' => $i['finish_at'] ? Carbon::parse($i['finish_at'])->format('H:i') : null,
        ])->toArray();
    }
}
