<?php

namespace App\Http\Controllers;

use App\Models\Attendance;

use Illuminate\Support\Facades\Auth;

class StampCorrectionRequestController extends Controller
{

    public function requestList()
    {
        $requestAttendances = Attendance::where('user_id', Auth::id())
            ->where('is_request', true)
            ->where('is_approved', false)
            ->with(['intermissions', 'user'])
            ->get();

        $approvedAttendances = Attendance::where('user_id', Auth::id())
            ->where('is_request', true)
            ->where('is_approved', true)
            ->with(['intermissions', 'user'])
            ->get();

        return view('stamp_request_list', compact('requestAttendances', 'approvedAttendances'));
    }
}
