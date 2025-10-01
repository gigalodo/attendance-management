<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;

class StaffController extends Controller
{

    public function index()
    {
        $users = User::where('role', 'user')->get();
        return view('admin_staff_list', compact('users'));
    }
}
