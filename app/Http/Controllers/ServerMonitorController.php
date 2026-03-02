<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class ServerMonitorController extends Controller
{
    public function index(): View
    {
        // ====================================================
        // PHẦN A: MONITORING (THU THẬP DỮ LIỆU TỪ SYSTEM)
        // ====================================================

        // 1. Lấy thông tin RAM (Đọc từ file hệ thống /proc/meminfo của Linux)
        $memInfo = file_get_contents("/proc/meminfo");
        preg_match('/MemTotal:\s+(\d+)/', $memInfo, $totalMatches);
        preg_match('/MemAvailable:\s+(\d+)/', $memInfo, $availableMatches);
        
        $totalRam = $totalMatches[1] / 1024; // Đổi đơn vị từ KB sang MB
        $availableRam = $availableMatches[1] / 1024;
        $usedRam = $totalRam - $availableRam;
        $ramPercent = round(($usedRam / $totalRam) * 100, 2); // Tính % RAM đang dùng

        // 2. Lấy thông tin CPU Load (Dùng hàm sys_getloadavg của PHP)
        // Hàm trả về mảng 3 giá trị: [1 phút trước, 5 phút trước, 15 phút trước]
        $load = sys_getloadavg();
        $cpuLoad = $load[0] * 100; // Lấy tải hiện tại nhân 100 để hiển thị

        // 3. Lấy thông tin Ổ cứng (Disk)
        $totalDisk = disk_total_space("."); // Tổng dung lượng thư mục hiện tại
        $freeDisk = disk_free_space(".");   // Dung lượng trống
        $usedDisk = $totalDisk - $freeDisk;
        $diskPercent = round(($usedDisk / $totalDisk) * 100, 2);

        // ====================================================
        // PHẦN B: DETECTION (PHÁT HIỆN BẤT THƯỜNG)
        // ====================================================
        $warnings = [];

        // Quy tắc 1: Nếu RAM dùng quá 80% => Cảnh báo quá tải bộ nhớ
        if ($ramPercent > 80) {
            $warnings[] = "CẢNH BÁO: Bộ nhớ RAM đang quá tải (" . $ramPercent . "%)";
        }

        // Quy tắc 2: Nếu Ổ cứng dùng quá 90% => Cảnh báo hết dung lượng
        if ($diskPercent > 90) {
            $warnings[] = "NGUY HIỂM: Ổ cứng sắp đầy (" . $diskPercent . "%)";
        }

        // Quy tắc 3: Nếu CPU Load quá cao (Trên Linux load có thể vượt 100% nếu đa nhân)
        if ($cpuLoad > 80) {
            $warnings[] = "CẢNH BÁO: CPU đang hoạt động với cường độ cao";
        }

        // ====================================================
        // PHẦN C: TRẢ DỮ LIỆU VỀ GIAO DIỆN
        // ====================================================
        return view('monitor', [
            'ram'       => $ramPercent,
            'cpu'       => $cpuLoad,
            'disk'      => $diskPercent,
            'total_ram' => round($totalRam / 1024, 1) . ' GB', // Đổi ra GB cho dễ đọc
            'warnings'  => $warnings
        ]);
    }
}