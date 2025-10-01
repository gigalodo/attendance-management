@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/stamp_request_list.css') }}">
@endsection

@section('content')

<div class="request-page">

    <h1 class="request-title">申請一覧</h1>

    <div class="request-tabs">
        <button class="request-tab-btn request-tab-btn--waiting active">承認待ち</button>
        <button class="request-tab-btn request-tab-btn--approved">承認済み</button>
    </div>
    @php
    use Carbon\Carbon;
    @endphp
    <div class="request-table-wrapper request-table-wrapper--waiting">
        <table class="request-table">
            <thead>
                <tr>
                    <th>状態</th>
                    <th>名前</th>
                    <th>対象日時</th>
                    <th>申請理由</th>
                    <th>申請日時</th>
                    <th>詳細</th>
                </tr>
            </thead>
            <tbody>
                @foreach($requestAttendances as $requestAttendance)
                <tr>
                    <td>承認待ち</td>
                    <td>{{ $requestAttendance->user->name }}</td>
                    <td>{{ Carbon::parse($requestAttendance->start_at)->format('Y/m/d')}}</td>
                    <td>{{ $requestAttendance->comments }}</td>
                    <td>{{ Carbon::parse($requestAttendance->created_at)->format('Y/m/d')}}</td>
                    <td><a href="/attendance/{{ $requestAttendance->id }}" class="detail-link">詳細</a></td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="request-table-wrapper request-table-wrapper--approved" style="display: none;">
        <table class="request-table">
            <thead>
                <tr>
                    <th>状態</th>
                    <th>名前</th>
                    <th>対象日時</th>
                    <th>申請理由</th>
                    <th>申請日時</th>
                    <th>詳細</th>
                </tr>
            </thead>
            <tbody>
                @foreach($approvedAttendances as $approvedAttendance)
                <tr>
                    <td>承認済み</td>
                    <td>{{ $approvedAttendance->user->name }}</td>
                    <td>{{ Carbon::parse($approvedAttendance->start_at)->format('Y/m/d')}}</td>
                    <td>{{ $approvedAttendance->comments }}</td>
                    <td>{{ Carbon::parse($approvedAttendance->created_at)->format('Y/m/d')}}</td>
                    <td><a href="/attendance/{{ $approvedAttendance->id }}" class="detail-link">詳細</a></td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const tabWaiting = document.querySelector('.request-tab-btn--waiting');
        const tabApproved = document.querySelector('.request-tab-btn--approved');
        const tableWaiting = document.querySelector('.request-table-wrapper--waiting');
        const tableApproved = document.querySelector('.request-table-wrapper--approved');

        function switchTab(tab) {
            if (tab === 'approved') {
                tableWaiting.style.display = 'none';
                tableApproved.style.display = 'block';
                tabWaiting.classList.remove('active');
                tabApproved.classList.add('active');
            } else {
                tableWaiting.style.display = 'block';
                tableApproved.style.display = 'none';
                tabWaiting.classList.add('active');
                tabApproved.classList.remove('active');
            }
        }

        tabWaiting.addEventListener('click', () => switchTab('waiting'));
        tabApproved.addEventListener('click', () => switchTab('approved'));
    });
</script>

@endsection