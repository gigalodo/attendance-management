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

Route::post('/register', [AuthController::class, 'authenticate']); //OK!!!!
Route::post('/login', [AuthController::class, 'login']); //OK!!!!
Route::post('/admin/login', [AuthController::class, 'adminLogin']); //OK!!!!
Route::get('/admin/login', [AuthController::class, 'showAdminLogin']); //OK!!!!

// Route::post('/logout', [AuthController::class, 'logout']);//うまくログアウトされないのでコメント

//一般ユーザー
Route::middleware(['auth', 'role:user'])->group(
    function () {

        Route::get('/attendance/list', [AttendanceController::class, 'index']); //OK!!!!

        Route::post('/attendance', [AttendanceController::class, 'storeAttendance']); //OK!!!!
    }
);

//管理者
Route::middleware(['auth', 'role:admin'])->group(
    function () {

        Route::get('/admin/attendance/list', [AdminAttendanceController::class, 'index']); //OK!!!!

        Route::get('/admin/staff/list', [StaffController::class, 'index']); //OK!!!!

        Route::get('/admin/attendance/staff/{user}', [AdminAttendanceController::class, 'staffIndex']); //OK!!!!

        Route::post('/stamp_correction_request/approve/{attendance}', [AdminRequestController::class, 'storeRequestDetail']); //OK!!!!

        Route::get('/stamp_correction_request/approve/{attendance}', [AdminRequestController::class, 'requestDetail']); //OK!!!!

        Route::post('/export', [AdminAttendanceController::class, 'export']); //OK!!!!
    }
);

Route::middleware('auth')->group(
    function () {

        Route::get('/stamp_correction_request/list', function (Request $request) {
            if (auth()->user()->role === 'admin') {
                return app(AdminRequestController::class)->requestList($request); //OK!!!!
            }
            return app(StampCorrectionRequestController::class)->requestList($request); //OK!!!!
        });

        Route::get('/attendance/{attendance}', function (Attendance $attendance) {
            if (auth()->user()->role === 'admin') {
                return app(AdminAttendanceController::class)->attendanceDetail($attendance); //OK!!!!
            }
            return app(AttendanceController::class)->attendanceDetail($attendance); //OK!!!!
        });

        Route::post('/attendance/{attendance}/request_update', function (Attendance $attendance, Request $request) {
            if (auth()->user()->role === 'admin') {
                return app(AdminAttendanceController::class)->storeAttendanceDetail($attendance, $request); //OK!!!!
            }
            return app(AttendanceController::class)->storeAttendanceDetail($attendance, $request); //OK!!!!
        });

        Route::get('/attendance', function (Request $request) {

            if ($request->has('date')) {
                if (auth()->user()->role === 'admin') {
                    return app(AdminAttendanceController::class)->attendanceEmpty($request); //OK!!!!
                }
                return app(AttendanceController::class)->attendanceEmpty($request); //OK!!!!
            } else {
                if (auth()->user()->role === 'user') {
                    return app(AttendanceController::class)->attendance($request); //OK!!!!
                }
            }
        });

        Route::post('/attendance/request_create', function (Request $request) {
            if (auth()->user()->role === 'admin') {
                return app(AdminAttendanceController::class)->storeAttendanceEmpty($request); //OK!!!!
            }
            return app(AttendanceController::class)->storeAttendanceEmpty($request); //OK!!!!
        });
    }
);
