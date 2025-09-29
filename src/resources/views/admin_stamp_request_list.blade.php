@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/admin_attendance_register.css') }}">
@endsection

@section('content')

<h1>申請一覧</h1>


<div class="mypage__tabs">
    <button class="mypage__tab-btn mypage__tab-btn--sell">承認待ち</button>
    <button class="mypage__tab-btn mypage__tab-btn--buy">承認済み</button>
</div>


<div class="mypage__grid mypage__grid--sell">
    <table>
        <tr>
            <th>状態</th>
            <th>名前</th>
            <th>対象日時</th>
            <th>申請理由</th>
            <th>申請日時</th>
            <th>詳細</th>
        </tr>
        @foreach($requestAttendances as $requestAttendance)
        <tr>
            <td>承認待ち</td>
            <td>{{$requestAttendance->user->name}}</td>
            <td>{{$requestAttendance->start_at}}</td>
            <td>{{$requestAttendance->comments}}</td>
            <td>{{$requestAttendance->created_at}}</td>
            <td><a href="/stamp_correction_request/approve/{{$requestAttendance->id}}">詳細</a></td>
        </tr>
        @endforeach
    </table>
</div>

<div class="mypage__grid mypage__grid--buy" style="display: none;">
    <table>
        <tr>
            <th>状態</th>
            <th>名前</th>
            <th>対象日時</th>
            <th>申請理由</th>
            <th>申請日時</th>
            <th>詳細</th>
        </tr>
        @foreach($approvedAttendances as $approvedAttendance)
        <tr>
            <td>承認待ち</td>
            <td>{{$approvedAttendance->user->name}}</td>
            <td>{{$approvedAttendance->start_at}}</td>
            <td>{{$approvedAttendance->comments}}</td>
            <td>{{$approvedAttendance->created_at}}</td>
            <td><a href="/attendance/{{$approvedAttendance->id}}">詳細</a></td>
        </tr>
        @endforeach
    </table>
</div>




<script>
    document.addEventListener('DOMContentLoaded', function() {
        const tabSell = document.querySelector('.mypage__tab-btn--sell');
        const tabBuy = document.querySelector('.mypage__tab-btn--buy');
        const gridSell = document.querySelector('.mypage__grid--sell');
        const gridBuy = document.querySelector('.mypage__grid--buy');

        function switchTab(tab) {
            if (tab === 'buy') {
                gridBuy.style.display = 'grid';
                gridSell.style.display = 'none';
                tabBuy.classList.add('active');
                tabSell.classList.remove('active');
            } else {
                gridSell.style.display = 'grid';
                gridBuy.style.display = 'none';
                tabSell.classList.add('active');
                tabBuy.classList.remove('active');
            }
            // history.pushState(null, '', `?page=${tab}`);
        }

        tabSell.addEventListener('click', () => switchTab('sell'));
        tabBuy.addEventListener('click', () => switchTab('buy'));

        // const page = new URLSearchParams(location.search).get('page');
        // switchTab(page === 'buy' ? 'buy' : 'sell');
    });
</script>

@endsection