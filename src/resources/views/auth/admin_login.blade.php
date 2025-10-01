@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/login.css') }}">
@endsection

@section('content')
<div class="login-form__content">
    <h1 class="login-form__heading">管理者ログイン</h1>
    <form class="form" action="/admin/login" method="post">
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
            <button class="form__button-submit" type="submit">管理者ログインする</button>
        </div>
    </form>
</div>
@endsection