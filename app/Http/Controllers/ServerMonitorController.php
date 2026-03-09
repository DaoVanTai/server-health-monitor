<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class ServerMonitorController extends Controller
{
    public function index(): View
    {
        // 1. Lấy thông tin RAM từ hệ thống Linux
        $memInfo = file_get_contents("/proc/meminfo");
        preg_match('/MemTotal:\s+(\d+)/', $memInfo, $totalMatches);
        preg_match('/MemAvailable:\s+(\d+)/', $memInfo, $availableMatches);
        
        $totalRam = $totalMatches[1] / 1024;
        $availableRam = $availableMatches[1] / 1024;
        $usedRam = $totalRam - $availableRam;
        $ramPercent = round(($usedRam / $totalRam) * 100, 2);

        // 2. Lấy % CPU Load
        $load = sys_getloadavg();
        $cpuLoad = $load[0] * 100;

        // 3. Lấy thông tin Ổ cứng
        $totalDisk = disk_total_space(".");
        $freeDisk = disk_free_space(".");
        $usedDisk = $totalDisk - $freeDisk;
        $diskPercent = round(($usedDisk / $totalDisk) * 100, 2);

        // --- LOGIC PHÁT HIỆN SỰ CỐ & GỬI TELEGRAM ---
        // Hiện tại đang để > 0 để bạn test thử, sau này hãy đổi thành > 90
        $isCritical = ($cpuLoad > 90 || $ramPercent > 90);
        
        if ($isCritical) {
            $this->sendTelegramAlert($cpuLoad, $ramPercent, $diskPercent);
        }

        return view('monitor', [
            'ram'       => $ramPercent,
            'cpu'       => $cpuLoad,
            'disk'      => $diskPercent,
            'total_ram' => round($totalRam / 1024, 1) . ' GB',
            'is_attacked' => $isCritical
        ]);
    }

    private function sendTelegramAlert($cpu, $ram, $disk)
    {
        $token = "8578604024:AAFkqh8-rHKmMjZL_aV6KzTXs2WLupjTcV4";
        $chatId = "1735680363";
        $message = "🚨 [SERVER ALERT] 🚨\n"
                 . "Cảnh báo: Hệ thống đang bị quá tải!\n"
                 . "━━━━━━━━━━━━━━━\n"
                 . "🖥 CPU Load: " . $cpu . "%\n"
                 . "🧠 RAM Usage: " . $ram . "%\n"
                 . "💾 Disk Usage: " . $disk . "%\n"
                 . "━━━━━━━━━━━━━━━\n"
                 . "📅 Time: " . date('Y-m-d H:i:s');

        $url = "https://api.telegram.org/bot$token/sendMessage?chat_id=$chatId&text=" . urlencode($message);
        
        // Sử dụng @ để bỏ qua cảnh báo nếu mạng máy ảo bị lag
        @file_get_contents($url);
    }
}