@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/attendance_detail.css') }}">
@endsection

@section('content')
<div class="attendance-detail">
    <h1 class="attendance-detail__title">勤怠詳細</h1>

    @php $index = 0; @endphp

    @if ($errors->any())
    <div class="attendance__alert--danger">
        <ul>
            @foreach ($errors->all() as $error)
            <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    <form action="{{ $attendance['id']
    ? '/attendance/' . $attendance['id'] . '/request_update'
    : '/attendance/request_create' }}"
        method="POST">
        @csrf

        <input type="hidden" name="date" value="{{isSet($date)?$date:''}}">
        <input type="hidden" name="user_id" value="{{isSet($attendance['user_id'])?$attendance['user_id']:''}}">

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
                <td><input type="text" class="attendance-detail__input attendance-detail__value" name="attendance_start_at" value="{{old('attendance_start_at',$attendance['start_at'])}}"></td>
                <td>～</td>
                <td><input type="text" class="attendance-detail__input attendance-detail__value" name="attendance_finish_at" value="{{old('attendance_finish_at',$attendance['finish_at'])}}"></td>
            </tr>
            @foreach($intermissions as $intermission)
            <tr>
                <th>休憩{{ ++$index }}</th>
                <td>
                    <input type="text" class="attendance-detail__input attendance-detail__value"
                        name="intermissions[{{ $index }}][start_at]"
                        value="{{ old('intermissions.' . $index . '.start_at', $intermission['start_at']) }}">
                </td>
                <td>～</td>
                <td>
                    <input type="text" class="attendance-detail__input attendance-detail__value"
                        name="intermissions[{{ $index }}][finish_at]"
                        value="{{ old('intermissions.' . $index . '.finish_at', $intermission['finish_at']) }}">
                </td>
            </tr>
            @endforeach
            <tr>
            <tr>
                <th>休憩{{ ++$index }}</th>
                <td><input type="text" class="attendance-detail__input attendance-detail__value" name="intermissions[{{$index}}][start_at]" value="{{ old('intermissions.' . $index . '.start_at') }}"></td>
                <td>～</td>
                <td><input type="text" class="attendance-detail__input attendance-detail__value" name="intermissions[{{$index}}][finish_at]" value="{{ old('intermissions.' . $index . '.finish_at') }}"></td>
            </tr>

            </tr>
            <tr>
                <th>備考</th>
                <td colspan="3"><textarea name="comments" class="attendance-detail__textarea">{{old('comments',$attendance['comments'])}}</textarea></td>
            </tr>
        </table>
        <div class="attendance-detail__actions">
            @if($attendance['is_approved'])
            <button type="submit" class="attendance-detail__button--approved" disabled>承認済み</button>
            @elseif($attendance['is_request'])
            <p class="attendance-detail__caution">*承認待ちのため修正はできません。</p>
            @else
            <button type="submit" class="attendance-detail__button">修正</button>
            @endif
        </div>
    </form>
</div>
@endsection