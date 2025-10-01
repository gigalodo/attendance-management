<?php

namespace App\Http\Controllers;

use App\Http\Requests\RegisterRequest;
use App\Http\Requests\LoginRequest;

use App\Models\User;

use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{

    public function authenticate(RegisterRequest $request)
    {
        $data = $request->All();

        $user =  User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'role' => 'user',
        ]);

        Auth::login($user);
        return redirect('/attendance');
    }

    public function login(LoginRequest $request)
    {
        $data = $request->All();

        if (Auth::attempt([
            'email' => $data['email'],
            'password' => $data['password'],
            'role' => 'user',
        ])) {
            return redirect('/attendance');
        }

        return back()->withErrors([
            'email' => 'ログイン情報が登録されていません',
        ])->withInput();
    }

    public function adminLogin(LoginRequest $request)
    {
        $data = $request->All();

        if (Auth::attempt([
            'email' => $data['email'],
            'password' => $data['password'],
            'role' => 'admin',
        ])) {
            return redirect('/admin/attendance/list');
        }

        return back()->withErrors([
            'email' => 'ログイン情報が登録されていません',
        ])->withInput();
    }

    public function showAdminLogin()
    {
        return view('auth.admin_login');
    }
}
