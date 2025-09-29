@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/admin_attendance_list.css') }}">
@endsection

@section('content')
<div class="attendance-page">

    <h1 class="attendance-title">{{$date['str']}}の勤怠</h1>

    <div class="attendance-card attendance-nav">
        <a href="/admin/attendance/list?date={{$date['before']}}" class="nav-arrow">
            <span class="arrow-icon">←</span><span class="arrow-text">前日</span>
        </a>
        <input id="date-picker" type="date" value="{{$date['day']}}">
        <a href="/admin/attendance/list?date={{$date['after']}}" class="nav-arrow">
            <span class="arrow-text">翌日</span><span class="arrow-icon">→</span>
        </a>
    </div>

    <div class="attendance-card">
        <table class="attendance-table">
            <thead>
                <tr>
                    <th>名前</th>
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
                    <td>{{ $row['name'] }}</td>
                    <td>{{ $row['start'] }}</td>
                    <td>{{ $row['finish'] }}</td>
                    <td>{{ $row['break'] }}</td>
                    <td>{{ $row['work'] }}</td>
                    <td class="text-center">
                        @if($row['attendance'])
                        <a href="/attendance/{{$row['attendance']->id}}">詳細</a>
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

<script>
    const picker = document.getElementById('date-picker');
    picker.addEventListener('keydown', function(e) {
        e.preventDefault();
    });
    picker.addEventListener('change', function() {
        if (this.value) {
            window.location.href = '/admin/attendance/list?date=' + this.value;
        }
    });
</script>
@endsection