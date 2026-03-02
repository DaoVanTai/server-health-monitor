<?php

namespace App\Http\Controllers;

use Illuminate\View\View;
use Illuminate\Support\Facades\Log;

class ServerMonitorController extends Controller
{
    public function index(): View
    {
        // ====================================================
        // PHẦN A: MONITORING (THU THẬP DỮ LIỆU TỪ SYSTEM)
        // ====================================================

        // 1. Lấy thông tin RAM (Đọc từ file hệ thống /proc/meminfo của Linux)
        $ramPercent = 0;
        $totalRamDisplay = 'N/A';
        try {
            $memInfo = @file_get_contents("/proc/meminfo");
            if ($memInfo !== false
                && preg_match('/MemTotal:\s+(\d+)/', $memInfo, $totalMatches)
                && preg_match('/MemAvailable:\s+(\d+)/', $memInfo, $availableMatches)
            ) {
                $totalRam = (float) $totalMatches[1] / 1024; // KB sang MB
                $availableRam = (float) $availableMatches[1] / 1024;
                $usedRam = $totalRam - $availableRam;
                $ramPercent = $totalRam > 0
                    ? round(($usedRam / $totalRam) * 100, 2)
                    : 0;
                $totalRamDisplay = round($totalRam / 1024, 1) . ' GB'; // MB sang GB
            } else {
                Log::warning('ServerMonitor: Không thể đọc thông tin RAM từ /proc/meminfo');
            }
        } catch (\Throwable $e) {
            Log::error('ServerMonitor: Lỗi khi đọc RAM - ' . $e->getMessage());
        }

        // 2. Lấy thông tin CPU Load (Dùng hàm sys_getloadavg của PHP)
        // Hàm trả về mảng 3 giá trị: [1 phút trước, 5 phút trước, 15 phút trước]
        // Giá trị load average chia cho số CPU cores để ra phần trăm chính xác
        $cpuLoad = 0;
        $load = @sys_getloadavg();
        if ($load !== false) {
            $cpuCores = 1;
            if (is_readable('/proc/cpuinfo')) {
                $cpuInfo = @file_get_contents('/proc/cpuinfo');
                if ($cpuInfo !== false) {
                    $cpuCores = max(1, substr_count($cpuInfo, 'processor'));
                }
            }
            $cpuLoad = round(($load[0] / $cpuCores) * 100, 2);
        } else {
            Log::warning('ServerMonitor: sys_getloadavg() không khả dụng trên hệ điều hành này');
        }

        // 3. Lấy thông tin Ổ cứng (Disk)
        $diskPercent = 0;
        $totalDisk = @disk_total_space("/");
        $freeDisk = @disk_free_space("/");
        if ($totalDisk !== false && $freeDisk !== false && $totalDisk > 0) {
            $usedDisk = $totalDisk - $freeDisk;
            $diskPercent = round(($usedDisk / $totalDisk) * 100, 2);
        } else {
            Log::warning('ServerMonitor: Không thể lấy thông tin ổ cứng');
        }

        // ====================================================
        // PHẦN B: DETECTION (PHÁT HIỆN BẤT THƯỜNG)
        // ====================================================
        $warnings = [];
        $thresholds = config('monitoring.thresholds');

        // Quy tắc 1: Nếu RAM dùng quá ngưỡng => Cảnh báo quá tải bộ nhớ
        if ($ramPercent > $thresholds['ram']) {
            $warnings[] = "CẢNH BÁO: Bộ nhớ RAM đang quá tải (" . $ramPercent . "%)";
        }

        // Quy tắc 2: Nếu Ổ cứng dùng quá ngưỡng => Cảnh báo hết dung lượng
        if ($diskPercent > $thresholds['disk']) {
            $warnings[] = "NGUY HIỂM: Ổ cứng sắp đầy (" . $diskPercent . "%)";
        }

        // Quy tắc 3: Nếu CPU Load quá cao
        if ($cpuLoad > $thresholds['cpu']) {
            $warnings[] = "CẢNH BÁO: CPU đang hoạt động với cường độ cao (" . $cpuLoad . "%)";
        }

        // ====================================================
        // PHẦN C: TRẢ DỮ LIỆU VỀ GIAO DIỆN
        // ====================================================
        return view('monitor', [
            'ram'       => $ramPercent,
            'cpu'       => $cpuLoad,
            'disk'      => $diskPercent,
            'total_ram' => $totalRamDisplay,
            'warnings'  => $warnings,
            'server_ip' => request()->server('SERVER_ADDR', 'Unknown'),
        ]);
    }
}