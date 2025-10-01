@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/admin_attendance_staff.css') }}">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
@endsection

@section('content')
<div class="attendance-page">

    <h1 class="attendance-title">{{ $user->name }}さんの勤怠</h1>

    <div class="attendance-card attendance-nav">
        <a href="/admin/attendance/staff/{{ $user->id }}?month={{ $month['before'] }}" class="nav-arrow">
            <span class="arrow-icon">←</span><span class="arrow-text">前月</span>
        </a>
        <div class="attendance-date-display">
            <i class="fa-regular fa-calendar-days calendar-icon"></i>
            <span>{{ $month['str'] }}</span>
        </div>

        <a href="/admin/attendance/staff/{{ $user->id }}?month={{ $month['after'] }}" class="nav-arrow">
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
                @foreach($rows as $row)
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
                        <a href="/attendance?date={{$row['today']}}&user={{$user->id}}">詳細修正！</a>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="export-form">
        <form action="/export" method="post">
            @csrf
            <input class="export__btn btn" type="submit" value="CSV出力">
            <input type="hidden" name="user_id" value="{{$user->id}}">
            <input type="hidden" name="month" value="{{$month['this_month']}}">
        </form>
    </div>

</div>
@endsection