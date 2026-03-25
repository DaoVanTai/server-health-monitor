<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\ServerMonitorController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\MetricController;
use App\Http\Controllers\NetworkController;
use App\Http\Controllers\FirewallController;
use App\Http\Controllers\AegisController;
use App\Http\Controllers\SecurityController;
use App\Http\Controllers\SystemLogController;

// Route cho Log & Analytics
Route::get('/analytics/logs', [SystemLogController::class, 'index'])->name('logs.index');

Route::get('/analytics/logs', [SystemLogController::class, 'index'])->name('logs.index');
// Route hút log SSH thật từ hệ điều hành
Route::post('/analytics/logs/sync-ssh', [SystemLogController::class, 'syncSshLogs'])->name('logs.sync_ssh');

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
// Sửa lại dòng này thành 3 lớp giáp
Route::middleware(['auth', 'enforce_2fa', '2fa'])->group(function () {
    
    // Dashboard chính
    Route::get('/monitor', [ServerMonitorController::class, 'index'])->name('monitor');
    
    // Trung tâm mạng
    Route::get('/network', [ServerMonitorController::class, 'networkIndex'])->name('network.index');

    // Các lệnh điều khiển Bot/Server nhạy cảm
    Route::post('/bot/command', [ServerMonitorController::class, 'handleCommand']);
    
    Route::get('/api/network/active-connections', [NetworkController::class, 'getActiveConnections'])->name('network.connections');

    Route::get('/api/metrics/history', [ServerMonitorController::class, 'getHistoricalMetrics'])->name('metrics.history');
    
    // KHU VỰC ĐIỀU KHIỂN DỊCH VỤ (SERVICE CONTROL)
    Route::get('/api/services/status', [ServerMonitorController::class, 'getServiceStatus']);
    Route::post('/api/services/control', [ServerMonitorController::class, 'controlService']);

    // ĐƯA CÁC TRANG QUAN TRỌNG VÀO BÊN TRONG KHU VỰC BẢO MẬT
    Route::get('/test-metrics', [MetricController::class, 'captureRealData']);
    Route::get('/firewall', [FirewallController::class, 'index'])->name('firewall.index');
    Route::post('/firewall/block', [FirewallController::class, 'blockIP'])->name('firewall.block');
    Route::post('/firewall/unblock/{id}', [FirewallController::class, 'unblockIP'])->name('firewall.unblock');
    
    // TRANG AI ĐÃ ĐƯỢC GIỮ LẠI ĐÚNG 1 ĐƯỜNG DẪN CHUẨN
    Route::get('/ai-intelligence', [AegisController::class, 'index'])->name('ai.index');

    //Máy quét nhật kí đăng nhập
    Route::get('/security/ssh-tracker', [App\Http\Controllers\SecurityController::class, 'sshTracker'])->name('ssh.tracker');
});

// ==========================================
// 5. HỆ THỐNG API (Lấy dữ liệu thời gian thực)
// ==========================================
Route::get('/api/server-status', [ServerMonitorController::class, 'getApiStatus']);
