<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\AuthController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\StampCorrectionRequestController;

use App\Http\Controllers\Admin\AttendanceController as AdminAttendanceController;
use App\Http\Controllers\Admin\StampCorrectionRequestController as AdminRequestController;
use App\Http\Controllers\Admin\StaffController;

use App\Models\Attendance;
use Illuminate\Http\Request;
/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', [AuthController::class, 'login']);

Route::post('/register', [AuthController::class, 'authenticate']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/admin/login', [AuthController::class, 'adminLogin']);
Route::get('/admin/login', [AuthController::class, 'showAdminLogin']);

Route::middleware(['auth', 'role:user'])->group(
    function () {

        Route::get('/attendance/list', [AttendanceController::class, 'index']);

        Route::post('/attendance', [AttendanceController::class, 'storeAttendance']);
    }
);

Route::middleware(['auth', 'role:admin'])->group(
    function () {

        Route::get('/admin/attendance/list', [AdminAttendanceController::class, 'index']);

        Route::get('/admin/staff/list', [StaffController::class, 'index']);

        Route::get('/admin/attendance/staff/{user}', [AdminAttendanceController::class, 'staffIndex']);

        Route::post('/stamp_correction_request/approve/{attendance}', [AdminRequestController::class, 'storeRequestDetail']);

        Route::get('/stamp_correction_request/approve/{attendance}', [AdminRequestController::class, 'requestDetail']);

        Route::post('/export', [AdminAttendanceController::class, 'export']);
    }
);

Route::middleware('auth')->group(
    function () {

        Route::get('/stamp_correction_request/list', function (Request $request) {
            if (auth()->user()->role === 'admin') {
                return app(AdminRequestController::class)->requestList($request);
            }
            return app(StampCorrectionRequestController::class)->requestList($request);
        });

        Route::get('/attendance/{attendance}', function (Attendance $attendance) {
            if (auth()->user()->role === 'admin') {
                return app(AdminAttendanceController::class)->attendanceDetail($attendance);
            }
            return app(AttendanceController::class)->attendanceDetail($attendance);
        });

        Route::post('/attendance/{attendance}/request_update', function (Attendance $attendance, Request $request) {
            if (auth()->user()->role === 'admin') {
                return app(AdminAttendanceController::class)->storeAttendanceDetail($attendance, $request);
            }
            return app(AttendanceController::class)->storeAttendanceDetail($attendance, $request);
        });

        Route::get('/attendance', function (Request $request) {

            if ($request->has('date')) {
                if (auth()->user()->role === 'admin') {
                    return app(AdminAttendanceController::class)->attendanceEmpty($request);
                }
                return app(AttendanceController::class)->attendanceEmpty($request);
            } else {
                if (auth()->user()->role === 'user') {
                    return app(AttendanceController::class)->attendance($request);
                }
            }
        });

        Route::post('/attendance/request_create', function (Request $request) {
            if (auth()->user()->role === 'admin') {
                return app(AdminAttendanceController::class)->storeAttendanceEmpty($request);
            }
            return app(AttendanceController::class)->storeAttendanceEmpty($request);
        });
    }
);
