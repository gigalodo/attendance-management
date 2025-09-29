@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/admin_attendance_staff.css') }}">
@endsection

@section('content')
<div class="attendance-page">

    <h1 class="attendance-title">{{$user->name}}さんの勤怠</h1>

    <div class="attendance-card attendance-nav">
        <a href="/admin/attendance/staff/{{$user->id}}?month={{$month['before']}}" class="nav-arrow">
            <span class="arrow-icon">←</span><span class="arrow-text">前月</span>
        </a>
        <!-- <input id="date-picker" type="date" value="{{$month['day']}}"> -->
        <label for="">{{$month['str']}}</label>
        <a href="/admin/attendance/staff/{{$user->id}}?month={{$month['after']}}" class="nav-arrow">
            <span class="arrow-text">翌月</span><span class="arrow-icon">→</span>
        </a>
    </div>


    <div>
        <table>
            <tr>
                <th>日付</th>
                <th>出勤</th>
                <th>退勤</th>
                <th>休憩</th>
                <th>合計</th>
                <th>詳細</th>
            </tr>
            @foreach($attendances as $attendance)
            <tr>
                <td>{{ $attendance['date'] }}</td>
                <td>{{ $attendance['start_at'] }}</td>
                <td>{{ $attendance['finish_at'] }}</td>
                <td>{{ $attendance['rest_at'] }}</td>
                <td>{{ $attendance['total_at'] }}</td>
                <td>
                    <a @if($attendance['id']) href="/attendance/{{$attendance['id']}}" @endif>詳細</a>
                </td>
            </tr>
            @endforeach
        </table>
    </div>
    @endsection