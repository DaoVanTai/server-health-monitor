<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\ServerMonitorController;
use App\Http\Controllers\Auth\LoginController;
// use App\Http\Controllers\Auth\RegisterController; // Đã vô hiệu hóa để bảo mật
use App\Http\Controllers\SecurityController; 
use App\Http\Controllers\MetricController;

// ==========================================
// 1. ĐIỀU HƯỚNG CỔNG CHÍNH
// ==========================================
Route::get('/', function () {
    // Nếu đã đăng nhập -> Vào Dashboard, chưa thì ra thẳng trang Đăng nhập
    return Auth::check() ? redirect('/monitor') : redirect()->route('login'); 
});

// ==========================================
// 2. KHU VỰC DÀNH CHO KHÁCH (GUEST)
// ==========================================
Route::middleware('guest')->group(function () {
    // [ĐÃ KHÓA CỬA] Tính năng Đăng ký tự do
    // Route::get('/register', [RegisterController::class, 'showRegistrationForm'])->name('register');
    // Route::post('/register', [RegisterController::class, 'register']);

    // Đăng nhập (Cửa vào duy nhất của hệ thống)
    Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [LoginController::class, 'login']);
});

// Đăng xuất (Dùng chung cho người đã login)
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

// ==========================================
// 3. KHU VỰC BẮT BUỘC ĐĂNG NHẬP (AUTH)
// Các trang này chỉ cần Login, chưa cần vượt qua lớp 2FA
// ==========================================
Route::middleware(['auth'])->group(function () {
    
    // --- Thiết lập 2FA lần đầu ---
    Route::get('/2fa/setup', [SecurityController::class, 'show2faForm'])->name('2fa.setup');
    Route::post('/2fa/enable', [SecurityController::class, 'enable2fa'])->name('2fa.enable');

    // --- Trang nhập mã 6 số (Challenge) ---
    // QUAN TRỌNG: Phải có cả GET để hiện form và POST để nhận mã
    Route::get('/2fa/verify', [SecurityController::class, 'verify2fa'])->name('2fa.verify');
    Route::post('/2fa/verify', [SecurityController::class, 'postVerify2fa'])->name('2fa.postVerify');
    
});

// ==========================================
// 4. KHU VỰC BẢO VỆ NGHIÊM NGẶT (AUTH + 2FA)
// Phải đăng nhập VÀ phải nhập đúng mã 6 số mới vào được
// ==========================================
Route::middleware(['auth', '2fa'])->group(function () {
    
    // Dashboard chính
    Route::get('/monitor', [ServerMonitorController::class, 'index'])->name('monitor');
    
    // Trung tâm mạng
    Route::get('/network', [ServerMonitorController::class, 'networkIndex'])->name('network.index');

    // Các lệnh điều khiển Bot/Server nhạy cảm
    Route::post('/bot/command', [ServerMonitorController::class, 'handleCommand']);
    
});

// ==========================================
// 5. HỆ THỐNG API (Lấy dữ liệu thời gian thực)
// ==========================================
Route::get('/api/server-status', [ServerMonitorController::class, 'getApiStatus']);

// Lấy thông số thực tế từ VPS Ubuntu
Route::get('/test-metrics', [MetricController::class, 'captureRealData']);