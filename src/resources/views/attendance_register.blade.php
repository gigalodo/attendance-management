@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/attendance_register.css') }}">
@endsection

@section('content')

@if (session('error_message'))
<div class="alert--error">{{ session('error_message') }}</div>
@endif

@php
use App\Models\Attendance;

$workState = "";

switch ($todayState) {
case Attendance::STATUS_BEFORE_WORK:
$workState = "勤務外";
break;
case Attendance::STATUS_WORKING:
$workState = "出勤中";
break;
case Attendance::STATUS_RESTING:
$workState = "休憩中";
break;
case Attendance::STATUS_FINISHED:
$workState = "退勤済";
break;
default:
$workState = "その他";
break;
}
@endphp

<p class="status-label">{{ $workState }}</p>

<p class="date-text">
    {{ now()->format('Y年n月j日') }}
    ({{ ['日','月','火','水','木','金','土'][now()->dayOfWeek] }})
</p>

<p id="time"></p>
<div class="button-group">
    <form action="/attendance" method="POST">
        @csrf
        @if($todayState === Attendance::STATUS_BEFORE_WORK)
        <button type="submit" class="attendance-button button-primary">出勤</button>
        @elseif($todayState === Attendance::STATUS_WORKING && $todayState !== Attendance::STATUS_RESTING)
        <button type="submit" class="attendance-button button-primary">退勤</button>
        @endif
        <input type="hidden" name="button_type" value="button1">
    </form>

    <form action="/attendance" method="POST">
        @csrf
        @if($todayState === Attendance::STATUS_WORKING)
        <button type="submit" class="attendance-button button-secondary">休憩入</button>
        @elseif($todayState === Attendance::STATUS_RESTING)
        <button type="submit" class="attendance-button button-secondary">休憩戻</button>
        @endif
        <input type="hidden" name="button_type" value="button2">
    </form>
</div>
@if($todayState === Attendance::STATUS_FINISHED)
<div class="thanks-message">お疲れ様でした。</div>
@endif

<script>
    function updateTime() {
        const now = new Date();
        const hours = String(now.getHours()).padStart(2, '0');
        const minutes = String(now.getMinutes()).padStart(2, '0');
        document.getElementById("time").textContent = `${hours}:${minutes}`;
    }

    updateTime();
    setInterval(updateTime, 1000);
</script>

@endsection