<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View; 

class ServerMonitorController extends Controller
{
    public function index(): View
    {
        // 1. Lấy thông tin RAM từ hệ thống Linux
        $memInfo = @file_get_contents("/proc/meminfo");
        $totalRam = 0; $usedRam = 0; $ramPercent = 0;
        
        if ($memInfo) {
            preg_match('/MemTotal:\s+(\d+)/', $memInfo, $totalMatches);
            preg_match('/MemAvailable:\s+(\d+)/', $memInfo, $availableMatches);
            
            $totalRam = isset($totalMatches[1]) ? $totalMatches[1] / 1024 : 0;
            $availableRam = isset($availableMatches[1]) ? $availableMatches[1] / 1024 : 0;
            $usedRam = $totalRam - $availableRam;
            $ramPercent = $totalRam > 0 ? round(($usedRam / $totalRam) * 100, 2) : 0;
        }

        // 2. Lấy % CPU Load
        $load = sys_getloadavg();
        $cpuLoad = $load ? $load[0] * 100 : 0;

        // 3. Lấy thông tin Ổ cứng
        $totalDisk = disk_total_space(".");
        $freeDisk = disk_free_space(".");
        $usedDisk = $totalDisk - $freeDisk;
        $diskPercent = $totalDisk > 0 ? round(($usedDisk / $totalDisk) * 100, 2) : 0;

        // --- LOGIC PHÁT HIỆN SỰ CỐ & GỬI TELEGRAM ---
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
    public function handleCommand(Request $request) 
    {
        $command = strtolower($request->input('command'));
        $reply = "";

        switch ($command) {
            case 'stop attack':
                @shell_exec("killall stress"); 
                $reply = "🛡️ HỆ THỐNG PHÒNG THỦ KÍCH HOẠT: Đã tiêu diệt toàn bộ các tiến trình gây quá tải!";
                break;

            case 'history cpu':
                $history = trim(@shell_exec("uptime | awk -F'load average:' '{ print $2 }'"));
                $reply = "📈 LỊCH SỬ CPU LOAD (1p, 5p, 15p): " . $history;
                break;

            case 'history ram':
                $history = trim(@shell_exec("free -m | grep -E 'Mem|Swap'"));
                $reply = "📊 DỮ LIỆU BỘ NHỚ HIỆN TẠI (MB):\n" . $history . "\n(Tính năng trích xuất Database lịch sử dài hạn đang được phát triển).";
                break;

            case 'clear cache':
                @shell_exec('sync; echo 1 > /proc/sys/vm/drop_caches');
                $reply = "✅ Đã giải phóng bộ nhớ đệm (Cache RAM) thành công!";
                break;

            default:
                $reply = "❌ Lệnh không xác định. Trợ lý hệ thống hỗ trợ các lệnh:\n- 'stop attack'\n- 'history cpu'\n- 'history ram'\n- 'clear cache'";
        }

        return response()->json(['reply' => $reply]);
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
        
        @file_get_contents($url);
    }
}