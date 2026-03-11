<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth; // Gọi thêm thư viện kiểm tra đăng nhập
use App\Http\Controllers\ServerMonitorController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;

// ==========================================
// 1. CỔNG CHÍNH (Tự động điều hướng)
// ==========================================
Route::get('/', function () {
    if (Auth::check()) {
        // Nếu đã đăng nhập -> Cho vào thẳng giao diện Dashboard
        return redirect('/monitor'); 
    }
    // Nếu chưa đăng nhập -> Ép văng ra trang Đăng ký đầu tiên
    return redirect()->route('register'); 
});

// ==========================================
// 2. KHU VỰC KHÁCH (Chưa đăng nhập mới được vào)
// ==========================================
Route::middleware('guest')->group(function () {
    // Trang Đăng ký
    Route::get('/register', [RegisterController::class, 'showRegistrationForm'])->name('register');
    Route::post('/register', [RegisterController::class, 'register']);

    // Trang Đăng nhập
    Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [LoginController::class, 'login']);
});

// ==========================================
// 3. NÚT ĐĂNG XUẤT
// ==========================================
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

// ==========================================
// 4. KHU VỰC BẢO MẬT (Phải đăng nhập mới thấy)
// ==========================================
Route::middleware(['auth'])->group(function () {
    Route::get('/monitor', function () {
        return view('monitor');
    })->name('monitor');
});

// ==========================================
// 5. API LẤY DỮ LIỆU TỪ MÁY ẢO UBUNTU (Giữ nguyên)
// ==========================================
Route::get('/api/server-status', function () {
    $cpuLoad = sys_getloadavg();
    $cpuPercent = min(round($cpuLoad[0] * 20, 2), 100); 

    $free = shell_exec('free -m');
    preg_match('/Mem:\s+(\d+)\s+(\d+)\s+(\d+)/', $free, $mem);
    $ramTotal = isset($mem[1]) ? round($mem[1] / 1024, 2) : 2.0;
    $ramUsed = isset($mem[2]) ? round($mem[2] / 1024, 2) : 1.0;
    $ramPercent = $ramTotal > 0 ? round(($ramUsed / $ramTotal) * 100, 2) : 0;

    $diskTotal = disk_total_space('/');
    $diskFree = disk_free_space('/');
    $diskUsed = $diskTotal - $diskFree;
    $diskPercent = $diskTotal > 0 ? round(($diskUsed / $diskTotal) * 100, 2) : 0;
    $diskFreeGb = round($diskFree / 1073741824, 2);

    $isAttacked = $cpuPercent > 85;

    $psOutput = shell_exec("ps -eo pid,comm,%mem,%cpu --sort=-%mem | head -n 6");
    $processes = [];
    if ($psOutput) {
        $lines = explode("\n", trim($psOutput));
        array_shift($lines);
        foreach($lines as $line) {
            $line = preg_replace('/\s+/', ' ', trim($line));
            if(empty($line)) continue;
            $parts = explode(' ', $line);
            if(count($parts) >= 4) {
                $processes[] = [
                    'pid' => $parts[0],
                    'name' => substr($parts[1], 0, 15),
                    'ram' => $parts[2],
                    'cpu' => $parts[3]
                ];
            }
        }
    }

    return response()->json([
        'cpu_percent' => $cpuPercent,
        'ram_percent' => $ramPercent,
        'ram_total'   => $ramTotal,
        'ram_used'    => $ramUsed,
        'disk_percent'=> $diskPercent,
        'disk_free'   => $diskFreeGb,
        'is_attacked' => $isAttacked,
        'processes'   => $processes 
    ]);
});

// ==========================================
// 6. ROUTE BOT COMMAND (Giữ nguyên)
// ==========================================
Route::post('/bot/command', [ServerMonitorController::class, 'handleCommand']);