@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/login.css') }}">
@endsection

@section('content')
<div class="login-form__content">
    <h1 class="login-form__heading">ログイン</h1>
    <form class="form" action="/login" method="post">
        @csrf
        <div class="form__group">
            <label class="form__label" for="email">メールアドレス</label>
            <input id="email" type="text" name="email" value="{{ old('email') }}" class="form__input">
            <div class="form__error">
                @error('email')
                {{ $message }}
                @enderror
            </div>
        </div>

        <div class="form__group">
            <label class="form__label" for="password">パスワード</label>
            <input id="password" type="password" name="password" class="form__input">
            <div class="form__error">
                @error('password')
                {{ $message }}
                @enderror
            </div>
        </div>

        <div class="form__button">
            <button class="form__button-submit" type="submit">ログインする</button>
        </div>
    </form>

    <div class="register__link">
        <a class="register__button-submit" href="/register">会員登録はこちら</a>
    </div>
</div>
@endsection