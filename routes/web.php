<?php

use Illuminate\Support\Facades\Route;

// Giao diện trang chủ
Route::get('/', function () {
    return view('welcome');
});

// API Lấy dữ liệu hệ thống
Route::get('/api/server-status', function () {
    // 1. Thông số CPU
    $cpuLoad = sys_getloadavg();
    $cpuPercent = min(round($cpuLoad[0] * 20, 2), 100); 

    // 2. Thông số RAM
    $free = shell_exec('free -m');
    preg_match('/Mem:\s+(\d+)\s+(\d+)\s+(\d+)/', $free, $mem);
    $ramTotal = isset($mem[1]) ? round($mem[1] / 1024, 2) : 2.0;
    $ramUsed = isset($mem[2]) ? round($mem[2] / 1024, 2) : 1.0;
    $ramPercent = $ramTotal > 0 ? round(($ramUsed / $ramTotal) * 100, 2) : 0;

    // 3. Thông số Ổ cứng
    $diskTotal = disk_total_space('/');
    $diskFree = disk_free_space('/');
    $diskUsed = $diskTotal - $diskFree;
    $diskPercent = $diskTotal > 0 ? round(($diskUsed / $diskTotal) * 100, 2) : 0;
    $diskFreeGb = round($diskFree / 1073741824, 2);

    $isAttacked = $cpuPercent > 85;

    // 4. TÍNH NĂNG MỚI: Lấy Top 5 tiến trình ngốn RAM nhất
    $psOutput = shell_exec("ps -eo pid,comm,%mem,%cpu --sort=-%mem | head -n 6");
    $processes = [];
    if ($psOutput) {
        $lines = explode("\n", trim($psOutput));
        array_shift($lines); // Bỏ qua dòng tiêu đề
        foreach($lines as $line) {
            $line = preg_replace('/\s+/', ' ', trim($line));
            if(empty($line)) continue;
            $parts = explode(' ', $line);
            if(count($parts) >= 4) {
                $processes[] = [
                    'pid' => $parts[0],
                    'name' => substr($parts[1], 0, 15), // Cắt ngắn tên cho đẹp
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
        'processes'   => $processes // Trả thêm mảng tiến trình
    ]);
});