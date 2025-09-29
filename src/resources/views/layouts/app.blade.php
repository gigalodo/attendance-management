<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Attendance Management</title>
    <link rel="stylesheet" href="{{ asset('css/sanitize.css') }}">
    <link rel="stylesheet" href="{{ asset('css/common.css') }}">
    @yield('css')
</head>

<body>
    <header class="header">
        <div class="header__inner">
            <div class="header__utilities">
                <a class="header__logo" href="/">
                    <img src="{{ asset('images/logo.svg') }}" alt="CoachTechロゴ" class="header__logo-image">
                </a>

                <nav>
                    <ul class="header__nav">

                        <li class="header__nav-item">
                            <a class="header__nav-link" href="/attendance">勤怠</a>
                        </li>

                        <li class="header__nav-item">
                            <a class="header__nav-link--sell" href="/attendance/list">勤怠一覧</a>
                        </li>

                        <li class="header__nav-item">
                            <a class="header__nav-link--sell" href="/stamp_correction_request/list">申請</a>
                        </li>

                        @if (Auth::check())
                        <li class="header__nav-item">
                            <form class="header__nav-form" action="/logout" method="post">
                                @csrf
                                <button class="header__nav-button">ログアウト</button>
                            </form>
                        </li>
                        @else
                        <li class="header__nav-item">
                            <a href="/login">
                                <button class="header__nav-button">ログイン</button>
                            </a>
                        </li>
                        @endif

                    </ul>
                </nav>

            </div>
        </div>
    </header>

    <main>
        @yield('content')
    </main>
</body>

</html>