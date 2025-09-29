@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/register.css') }}">
@endsection

@section('content')
<div class="register-form__content">
    <h1 class="register-form__heading">会員登録</h1>
    <form class="form" action="/register" method="post">
        @csrf

        <div class="form__group">
            <label class="form__label" for="name">名前</label>
            <input id="name" type="text" name="name" value="{{ old('name') }}" class="form__input">
            <div class="form__error">
                @error('name')
                {{ $message }}
                @enderror
            </div>
        </div>

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

        <div class="form__group">
            <label class="form__label" for="password_confirmation">パスワード確認</label>
            <input id="password_confirmation" type="password" name="password_confirmation" class="form__input">
        </div>

        <div class="form__button">
            <button class="form__button-submit" type="submit">登録する</button>
        </div>
    </form>

    <div class="login__link">
        <a class="login__button-submit" href="/login">ログインはこちら</a>
    </div>
</div>
@endsection