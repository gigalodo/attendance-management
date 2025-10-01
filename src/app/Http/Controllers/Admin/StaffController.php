<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Attendance;
use App\Models\Intermission;

use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class StaffController extends Controller
{

    public function index()
    {
        $users = User::where('role', 'user')->get();
        return view('admin_staff_list', compact('users'));
    }
}
