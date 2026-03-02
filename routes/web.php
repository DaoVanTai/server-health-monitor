<?php

use Illuminate\Support\Facades\Route;
// Nạp cái Controller giám sát mà chúng ta vừa tạo ở Phần 1 vào đây
use App\Http\Controllers\ServerMonitorController;

// Đặt quy tắc: Khi người dùng vào trang chủ ('/') -> Chạy hàm index của ServerMonitorController
Route::get('/', [ServerMonitorController::class, 'index']);