@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/attendance_list.css') }}">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
@endsection

@section('content')
<div class="attendance-page">

    <h1 class="attendance-title">勤怠一覧</h1>

    <div class="attendance-card attendance-nav">
        <a href="/attendance/list?month={{$month['before']}}" class="nav-arrow">
            <span class="arrow-icon">←</span><span class="arrow-text">前月</span>
        </a>

        <div class="attendance-date-display">
            <i class="fa-regular fa-calendar-days calendar-icon"></i>
            <span>{{ $month['str'] }}</span>
        </div>

        <a href="/attendance/list?month={{$month['after']}}" class="nav-arrow">
            <span class="arrow-text">翌月</span><span class="arrow-icon">→</span>
        </a>
    </div>

    <div class="attendance-card">
        <table class="attendance-table">
            <thead>
                <tr>
                    <th>日付</th>
                    <th>出勤</th>
                    <th>退勤</th>
                    <th>休憩</th>
                    <th>合計</th>
                    <th>詳細</th>
                </tr>
            </thead>
            <tbody>
                @forelse($rows as $row)
                <tr>
                    <td>{{ $row['date'] }}</td>
                    <td>{{ $row['start_at'] }}</td>
                    <td>{{ $row['finish_at'] }}</td>
                    <td>{{ $row['rest_at'] }}</td>
                    <td>{{ $row['total_at'] }}</td>
                    <td class="text-center">
                        @if($row['id'])
                        <a href="/attendance/{{ $row['id'] }}">詳細</a>
                        @else
                        <a href="/attendance?date={{$row['today']}}">詳細</a>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="attendance-empty">データがありません。</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

</div>
@endsection