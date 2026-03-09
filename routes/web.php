<?php

use Illuminate\Support\Facades\Route;
// Nạp cái Controller giám sát mà chúng ta vừa tạo ở Phần 1 vào đây
use App\Http\Controllers\ServerMonitorController;

// Đặt quy tắc: Khi người dùng vào trang chủ ('/') -> Chạy hàm index của ServerMonitorController để load giao diện
Route::get('/', [ServerMonitorController::class, 'index']);

// ----------------------------------------------------------------------
// API MỚI: Cung cấp dữ liệu thật của máy ảo Ubuntu cho giao diện (Cập nhật mỗi 5 giây)
// ----------------------------------------------------------------------
Route::get('/api/server-status', function () {
    // 1. Lấy thông số RAM thật từ lệnh 'free' của Ubuntu
    $free = shell_exec('free -m');
    $free_arr = explode("\n", trim($free));
    $mem = explode(" ", preg_replace('!\s+!', ' ', $free_arr[1]));
    
    // Xử lý logic chia dung lượng RAM
    $memTotal = isset($mem[1]) ? round($mem[1] / 1024, 2) : 0; // GB
    $memUsed = isset($mem[2]) ? round($mem[2] / 1024, 2) : 0;  // GB
    $memPercent = $memTotal > 0 ? round(($memUsed / $memTotal) * 100, 2) : 0;

    // 2. Lấy % CPU thật đang sử dụng
    $cpuTop = shell_exec("top -bn1 | grep 'Cpu(s)'");
    preg_match('/(\d+\.\d+)\s*id/', $cpuTop, $matches);
    $cpuIdle = isset($matches[1]) ? (float)$matches[1] : 100;
    $cpuPercent = round(100 - $cpuIdle, 2);

    // 3. Lấy thông số Ổ Cứng (Disk) thật của phân vùng gốc '/'
    $diskTotal = round(disk_total_space('/') / 1024 / 1024 / 1024, 2); // GB
    $diskFree = round(disk_free_space('/') / 1024 / 1024 / 1024, 2);   // GB
    $diskUsed = $diskTotal - $diskFree;
    $diskPercent = $diskTotal > 0 ? round(($diskUsed / $diskTotal) * 100, 2) : 0;

    // 4. Logic phát hiện Sự cố / Bị Tấn công
    // Hệ thống sẽ nháy đỏ cảnh báo nếu CPU > 90% hoặc RAM > 95%
    $isUnderAttack = false;
    if ($cpuPercent > 90 || $memPercent > 95) {
        $isUnderAttack = true;
    }

    // Trả toàn bộ dữ liệu về dạng JSON
    return response()->json([
        'cpu_percent' => $cpuPercent,
        'ram_percent' => $memPercent,
        'ram_total'   => $memTotal,
        'ram_used'    => $memUsed,
        'disk_percent'=> $diskPercent,
        'disk_free'   => $diskFree,
        'is_attacked' => $isUnderAttack
    ]);
});