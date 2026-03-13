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

        // --- NGÃ RẼ 1: CÁC LỆNH HỆ THỐNG ƯU TIÊN (LẤY TỪ DATABASE) ---
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
                        $reply = "Kho lưu trữ trống. Đang thu thập dữ liệu (1 phút/lần)...";
                    } else {
                        $reply = "📈 LỊCH SỬ " . strtoupper($type) . " (5 PHÚT GẦN NHẤT):\n";
                        foreach($logs as $log) {
                            $val = ($type == 'cpu') ? $log->cpu_percent : (($type == 'ram') ? $log->ram_percent : $log->disk_percent);
                            $reply .= "⏱ " . $log->created_at->format('H:i') . " ➔ {$val}%\n";
                        }
                    }
                    break;
            }
            return response()->json(['reply' => $reply]);
        }

        // --- NGÃ RẼ 2: GIAO CHO AI GEMINI (PHẢI KHỚP VỚI BẢN 2.5 FLASH) ---
        // Tự động gọt bỏ mọi dấu cách hoặc ký tự lạ từ file .env
        $apiKey = trim(env('GEMINI_API_KEY'));
        
        if (!$apiKey) {
            return response()->json(['reply' => '⚠️ Hệ thống chưa nhận được API Key. Hãy chạy lệnh php artisan config:clear']);
        }

        try {
            // CẬP NHẬT: Sử dụng model gemini-2.5-flash theo đúng Rate Limit của bạn
            $url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key=" . $apiKey;

            $response = Http::withHeaders(['Content-Type' => 'application/json'])
                ->post($url, [
                    'contents' => [
                        ['parts' => [['text' => "Bạn là trợ lý AI giám sát dự án Server Health Monitoring & Detection System. Hãy trả lời ngắn gọn: " . $rawCommand]]]
                    ]
                ]);

            if ($response->successful()) {
                $reply = $response->json('candidates.0.content.parts.0.text');
            } else {
                // Phân tích lỗi cụ thể để bạn dễ sửa
                $status = $response->status();
                $errorMsg = $response->json('error.message') ?? 'Lỗi không xác định';
                $reply = "⚠️ AI báo lỗi ($status): $errorMsg";
            }
        } catch (\Exception $e) {
            $reply = "⚠️ Lỗi kết nối: " . $e->getMessage();
        }

        return response()->json(['reply' => $reply]);
    }

    private function sendTelegramAlert($cpu, $ram, $disk)
    {
        $token = "8578604024:AAFkqh8-rHKmMjZL_aV6KzTXs2WLupjTcV4";
        $chatId = "1735680363";
        $message = "🚨 [SERVER ALERT] 🚨\nCảnh báo: Hệ thống quá tải!\n"
                 . "CPU: $cpu% | RAM: $ram% | DISK: $disk%\n"
                 . "📅 Time: " . date('Y-m-d H:i:s');

        $url = "https://api.telegram.org/bot$token/sendMessage?chat_id=$chatId&text=" . urlencode($message);
        @file_get_contents($url);
    }
}