<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class Enforce2FASetup
{
    public function handle(Request $request, Closure $next)
    {
        // Nếu người dùng đã đăng nhập nhưng cột Secret trong Database bị trống
        if (Auth::check() && empty(Auth::user()->google2fa_secret)) {
            // Lập tức bẻ lái, tống cổ sang trang yêu cầu quét mã QR
            return redirect()->route('2fa.setup');
        }

        // Nếu đã có mã Secret rồi thì cho phép đi tiếp qua trạm gác tiếp theo
        return $next($request);
    }
}