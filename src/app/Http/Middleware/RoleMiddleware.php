<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RoleMiddleware
{
    public function handle(Request $request, Closure $next, ...$roles)
    {
        if (!Auth::check()) {
            return redirect('/login');
        }

        if (!in_array(Auth::user()->role, $roles)) {
            abort(403, 'アクセス権限がありません');
        }

        return $next($request);
    }

    // public function handle($request, Closure $next, $role)
    // {
    //     $user = $request->user();

    //     if (!$user) {
    //         abort(403, '認証されていません');
    //     }

    //     // 管理者 or 一般どちらかに通す
    //     if ($role === 'user' && $user->role === 'user') {
    //         return $next($request);
    //     }

    //     if ($role === 'admin' && $user->role === 'admin') {
    //         return $next($request);
    //     }

    //     // if ($request->is('stamp_correction_request/list')) {
    //     //     if ($user->role === 'admin') {
    //     //         return app(\App\Http\Controllers\Admin\StampCorrectionRequestController::class)->requestList($request);
    //     //     } elseif ($user->role === 'user') {
    //     //         return app(\App\Http\Controllers\StampCorrectionRequestController::class)->requestList($request);
    //     //     }
    //     // }
    //     if ($request->is('stamp_correction_request/list')) {
    //         if ($user->role === 'admin') {
    //             return app()->call(
    //                 \App\Http\Controllers\Admin\StampCorrectionRequestController::class . '@requestList',
    //                 ['request' => $request]
    //             );
    //         } elseif ($user->role === 'user') {
    //             return app()->call(
    //                 \App\Http\Controllers\StampCorrectionRequestController::class . '@requestList',
    //                 ['request' => $request]
    //             );
    //         }
    //     }

    //     abort(403, 'アクセス権限がありません');
    // }
}
