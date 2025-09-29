@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/attendance_detail.css') }}">
@endsection

@section('content')
<div class="attendance-detail">
    <h1 class="attendance-detail__title">勤怠詳細</h1>

    @php $index = 0; @endphp

    <form action="/attendance/{{$attendance['id']}}/request_create" method="POST">
        @csrf
        <table class="attendance-detail__table">
            <tr>
                <th>名前</th>
                <td colspan="3" class="attendance-detail__value">{{$attendance['name']}}</td>
            </tr>
            <tr>
                <th>日付</th>
                <td class="attendance-detail__value">{{$attendance['year']}}</td>
                <td></td>
                <td class="attendance-detail__value">{{$attendance['date']}}</td>
            </tr>
            <tr>
                <th>出勤・退勤</th>
                <td><input type="text" class="attendance-detail__input attendance-detail__value" name="attendance_start_at" value="{{$attendance['start_at']}}"></td>
                <td>～</td>
                <td><input type="text" class="attendance-detail__input attendance-detail__value" name="attendance_finish_at" value="{{$attendance['finish_at']}}"></td>
            </tr>
            @foreach($intermissions as $intermission)
            <tr>
                <th>休憩{{++$index}}</th>
                <td><input type="text" class="attendance-detail__input attendance-detail__value" name="intermissions[{{$index}}][start_at]" value="{{$intermission['start_at']}}"></td>
                <td>～</td>
                <td><input type="text" class="attendance-detail__input attendance-detail__value" name="intermissions[{{$index}}][finish_at]" value="{{$intermission['finish_at']}}"></td>
            </tr>
            @endforeach
            <tr>
                <th>休憩{{++$index}}</th>
                <td><input type="text" class="attendance-detail__input attendance-detail__value" name="intermissions[{{$index}}][start_at]" value=""></td>
                <td>～</td>
                <td><input type="text" class="attendance-detail__input attendance-detail__value" name="intermissions[{{$index}}][finish_at]" value=""></td>
            </tr>
            <tr>
                <th>備考</th>
                <td colspan="3"><textarea class="attendance-detail__textarea">{{$attendance['comments']}}</textarea></td>
            </tr>
        </table>
        <div class="attendance-detail__actions">
            <button type="submit" class="attendance-detail__button">修正</button>
        </div>
    </form>
</div>
@endsection