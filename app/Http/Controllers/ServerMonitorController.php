<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View; 
use Illuminate\Support\Facades\Http;

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
        $rawCommand = $request->input('command');
        $command = strtolower(trim($rawCommand));
        $reply = "";

        // --- DANH SÁCH CÁC LỆNH HỆ THỐNG (OFFLINE) ---
        $systemCommands = ['stop attack', 'clear cache', 'history cpu', 'history ram', 'history disk'];

        if (in_array($command, $systemCommands)) {
            switch ($command) {
                case 'stop attack':
                    @shell_exec("killall stress"); 
                    $reply = "🛡️ HỆ THỐNG PHÒNG THỦ: Đã tiêu diệt toàn bộ các tiến trình gây quá tải!";
                    break;
                case 'clear cache':
                    @shell_exec('sync; echo 1 > /proc/sys/vm/drop_caches');
                    $reply = "✅ Đã giải phóng bộ nhớ đệm (Cache RAM) thành công!";
                    break;
                case 'history cpu':
                case 'history ram':
                case 'history disk':
                    $type = str_replace('history ', '', $command);
                    $logs = \App\Models\ServerMetric::orderBy('created_at', 'desc')->take(5)->get();
                    if ($logs->isEmpty()) {
                        $reply = "Kho lưu trữ trống. Đang chờ thu thập dữ liệu từ Crontab...";
                    } else {
                        $reply = "📈 LỊCH SỬ " . strtoupper($type) . ":\n";
                        foreach($logs as $log) {
                            $val = ($type == 'cpu') ? $log->cpu_percent : (($type == 'ram') ? $log->ram_percent : $log->disk_percent);
                            $reply .= "⏱ " . $log->created_at->format('H:i') . " ➔ {$val}%\n";
                        }
                    }
                    break;
            }
        } else {
            // PHẢN HỒI MẶC ĐỊNH KHI KHÔNG CÓ AI
            $reply = "🤖 Chào Admin! Hiện tại tính năng AI đang tạm đóng để bảo trì. Bạn có thể sử dụng các lệnh hệ thống như: 'history cpu', 'clear cache', hoặc 'stop attack' để quản lý máy chủ.";
        }

        return response()->json(['reply' => $reply]);
    }

    private function sendTelegramAlert($cpu, $ram, $disk)
    {
        $token = "8578604024:AAFkqh8-rHKmMjZL_aV6KzTXs2WLupjTcV4";
        $chatId = "1735680363";
        $message = "🚨 [SERVER ALERT] 🚨\nCảnh báo: Hệ thống quá tải!\n"
                 . "CPU: $cpu% | RAM: $ram% | DISK: $disk%";
        @file_get_contents("https://api.telegram.org/bot$token/sendMessage?chat_id=$chatId&text=" . urlencode($message));
    }
}