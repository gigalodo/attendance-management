<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\StampCorrectionRequestController;

use App\Http\Controllers\Admin\AuthController as AdminAuthController;
use App\Http\Controllers\Admin\AttendanceController as AdminAttendanceController;
use App\Http\Controllers\Admin\StampCorrectionRequestController as AdminRequestController;

use App\Http\Controllers\Admin\StaffController;


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

Route::get('/', function () {
    return view('index');
});

// Route::get('/', [ItemController::class, 'index']);

Route::post('/register', [AuthController::class, 'authenticate']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/admin/login', [AuthController::class, 'adminLogin']);

Route::get('/admin/login', [AuthController::class, 'showAdminLogin']);









//一般ユーザー
Route::middleware(['auth', 'role:user'])->group(
    function () {

        Route::post('/attendance/list', [AttendanceController::class, 'storeIndex']);

        Route::get('/attendance/list', [AttendanceController::class, 'index']); //勤怠一覧画面


        Route::post('/attendance', [AttendanceController::class, 'storeAttendance']);

        Route::post('/attendance/{attendance}/request_create', [AttendanceController::class, 'storeAttendanceDetail']);

        Route::get('/attendance', [AttendanceController::class, 'attendance']); //勤怠登録画面

        // Route::get('/stamp_correction_request/list', [StampCorrectionRequestController::class, 'requestList']); //申請一覧画面

        // Route::post('/stamp_correction_request/list', [StampCorrectionRequestController::class, 'storeRequestList']);
    }
);


// Route::post('/attendance/{attendance}/update', [AttendanceController::class, 'storeAttendanceDetail']);

//管理者
Route::middleware(['auth', 'role:admin'])->group(
    function () {

        Route::post('/admin/attendance/list', [AdminAttendanceController::class, 'storeIndex']);

        Route::get('/admin/attendance/list', [AdminAttendanceController::class, 'index']);

        Route::get('/admin/attendance/staff/{user}', [AdminAttendanceController::class, 'staffIndex']);

        Route::get('/admin/staff/list', [StaffController::class, 'index']);

        // Route::get('/stamp_correction_request/list', [AdminRequestController::class, 'requestList']); //申請一覧画面

        // Route::post('/stamp_correction_request/list', [AdminRequestController::class, 'storeRequestList']);

        Route::post('/stamp_correction_request/approve/{attendance}', [AdminRequestController::class, 'storeRequestDetail']);

        Route::get('/stamp_correction_request/approve/{attendance}', [AdminRequestController::class, 'requestDetail']);
    }
);


Route::middleware('auth')->group(
    function () {
        Route::post('/attendance/{id}', [AttendanceController::class, 'storeAttendanceDetail']);

        Route::get('/attendance/{attendance}', [AttendanceController::class, 'attendanceDetail']); //勤怠詳細画面

        // Route::get('/stamp_correction_request/list', [StampCorrectionRequestController::class, 'requestList']); //申請一覧画面

        // Route::post('/stamp_correction_request/list', [StampCorrectionRequestController::class, 'storeRequestList']);
        Route::get('/stamp_correction_request/list', function (Request $request) {
            if (auth()->user()->role === 'admin') {
                return app(AdminRequestController::class)->requestList($request);
            }
            return app(StampCorrectionRequestController::class)->requestList($request);
        });
    }
);



// Route::middleware('auth')->get('/stamp_correction_request/list', function (Request $request) {
//     if (auth()->user()->role === 'admin') {
//         return app(\App\Http\Controllers\Admin\StampCorrectionRequestController::class)
//             ->requestList($request);
//     }
//     return app(\App\Http\Controllers\StampCorrectionRequestController::class)
//         ->requestList($request);
// });

// Route::middleware(['auth'])->group(function () {
//     Route::get('/stamp_correction_request/list', function () {
//         if (auth()->user()->role === 'admin') {
//             return app(AdminRequestController::class)->requestList();
//         }
//         return app(StampCorrectionRequestController::class)->requestList();
//     });
// });
