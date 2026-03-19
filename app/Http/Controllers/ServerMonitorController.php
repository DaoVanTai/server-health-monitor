<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View; 
use Illuminate\Support\Facades\Http;
use App\Models\ServerMetric; // BỔ SUNG: Khai báo Model để lấy dữ liệu lịch sử

class ServerMonitorController extends Controller
{
    /**
     * Tải giao diện Dashboard lần đầu
     */
    public function index(): View
    {
        $data = $this->getServerMetrics();
        return view('monitor', $data);
    }

    /**
     * Hàm API để JavaScript (AJAX) gọi mỗi 5 giây
     */
    public function getApiStatus()
    {
        $data = $this->getServerMetrics();
        return response()->json($data);
    }

    /**
     * BỔ SUNG: Hàm API lấy dữ liệu lịch sử và các đỉnh (Spikes)
     */
    public function getHistoricalMetrics()
    {
        // 1. Lấy dữ liệu của 6 giờ qua, sắp xếp theo thời gian cũ -> mới để vẽ biểu đồ
        $history = ServerMetric::where('created_at', '>=', now()->subHours(6))
                    ->orderBy('created_at', 'asc')
                    ->get();

        // 2. Tìm ra 3 thời điểm (Đỉnh) mà CPU hoạt động cao nhất
        $topSpikes = ServerMetric::where('created_at', '>=', now()->subHours(6))
                    ->orderBy('cpu_percent', 'desc')
                    ->limit(3)
                    ->get();

        return response()->json([
            'history' => $history,
            'spikes'  => $topSpikes
        ]);
    }

    /**
     * API: Lấy trạng thái các dịch vụ lõi (ĐÃ NÂNG CẤP ĐƯỜNG DẪN TUYỆT ĐỐI)
     */
    public function getServiceStatus()
    {
        $services = ['nginx', 'mysqld']; 
        $status = [];

        foreach ($services as $svc) {
            // Dùng đường dẫn tuyệt đối /usr/bin/systemctl để tránh lỗi môi trường của PHP
            $check = trim(@shell_exec("/usr/bin/systemctl is-active $svc 2>/dev/null"));
            $status[$svc] = ($check === 'active') ? 'running' : 'stopped';
        }

        return response()->json($status);
    }

    /**
     * API: Ra lệnh điều khiển dịch vụ (ĐÃ NÂNG CẤP BẮT LỖI CHI TIẾT)
     */
    public function controlService(Request $request)
    {
        $service = $request->input('service');
        $action = $request->input('action'); // restart, stop, start
        
        $allowed_services = ['nginx', 'mysqld'];
        $allowed_actions = ['restart', 'stop', 'start'];

        if (in_array($service, $allowed_services) && in_array($action, $allowed_actions)) {
            // Lấy tên User đang chạy lệnh web (thường là www)
            $currentUser = trim(shell_exec('whoami'));

            // Lệnh sudo gọi thẳng đường dẫn tuyệt đối
            $command = "sudo /usr/bin/systemctl $action $service 2>&1";
            $output = shell_exec($command);

            return response()->json([
                'success' => true, 
                // Trả về thông báo siêu chi tiết để dễ dàng bắt bệnh
                'message' => "🧑‍💻 User thực thi: $currentUser\n⚡ Lệnh: $command\n🐧 Phản hồi từ Linux:\n" . ($output ?: '[Thành công - Không có lỗi]')
            ]);
        }

        return response()->json(['success' => false, 'message' => 'Lệnh không hợp lệ!']);
    }

    /**
     * Hàm dùng chung để lấy tất cả thông số hệ thống
     */
    private function getServerMetrics()
    {
        // 1. RAM Metrics
        $memInfo = @file_get_contents("/proc/meminfo");
        $totalRam = 0; $usedRam = 0; $ramPercent = 0;
        if ($memInfo) {
            preg_match('/MemTotal:\s+(\d+)/', $memInfo, $totalMatches);
            preg_match('/MemAvailable:\s+(\d+)/', $memInfo, $availableMatches);
            $totalRam = isset($totalMatches[1]) ? round($totalMatches[1] / 1024 / 1024, 2) : 0; // GB
            $availableRam = isset($availableMatches[1]) ? round($availableMatches[1] / 1024 / 1024, 2) : 0;
            $usedRam = round($totalRam - $availableRam, 2);
            $ramPercent = $totalRam > 0 ? round(($usedRam / $totalRam) * 100, 2) : 0;
        }

        // 2. CPU Metrics
        $load = sys_getloadavg();
        $cpuLoad = $load ? round($load[0] * 100, 2) : 0;

        // 3. Disk Metrics
        $totalDisk = disk_total_space("/");
        $freeDisk = disk_free_space("/");
        $usedDisk = $totalDisk - $freeDisk;
        $diskPercent = $totalDisk > 0 ? round(($usedDisk / $totalDisk) * 100, 2) : 0;
        $diskFreeGB = round($freeDisk / 1024 / 1024 / 1024, 2);

        // 4. CPU Cores
        $cores = [];
        $cpuStats = @file("/proc/stat");
        if ($cpuStats) {
            foreach ($cpuStats as $line) {
                if (preg_match('/^cpu[0-9]/', $line)) {
                    $info = preg_split('/\s+/', trim($line));
                    $cores[] = ['name' => strtoupper($info[0]), 'val' => rand(5, 35)]; // Minh họa tải từng nhân
                }
            }
        }

        // 5. Network Traffic (MB)
        $network = ['in' => 0, 'out' => 0];
        $netStats = @file("/proc/net/dev");
        if ($netStats) {
            foreach ($netStats as $line) {
                if (preg_match('/(eth0|ens33|enp|wlan)/', $line)) {
                    $info = preg_split('/\s+/', trim($line));
                    $network['in'] += round($info[1] / 1024 / 1024, 2);
                    $network['out'] += round($info[9] / 1024 / 1024, 2);
                }
            }
        }

        // 6. Top Processes (Top 10 ngốn CPU)
        $processes = [];
        exec("ps -eo pid,comm,%cpu,%mem --sort=-%cpu | head -n 11", $output);
        foreach (array_slice($output, 1) as $line) {
            $data = preg_split('/\s+/', trim($line));
            if (count($data) >= 4) {
                $processes[] = [
                    'pid'  => $data[0],
                    'name' => $data[1],
                    'cpu'  => $data[2],
                    'ram'  => $data[3]
                ];
            }
        }

        $isCritical = ($cpuLoad > 90 || $ramPercent > 90);
        if ($isCritical) {
            $this->sendTelegramAlert($cpuLoad, $ramPercent, $diskPercent);
        }

        return [
            'cpu_percent' => $cpuLoad,
            'ram_percent' => $ramPercent,
            'ram_total'   => $totalRam,
            'ram_used'    => $usedRam,
            'disk_percent'=> $diskPercent,
            'disk_free'   => $diskFreeGB,
            'total_ram'   => $totalRam . ' GB',
            'is_attacked' => $isCritical,
            'cores'       => $cores,
            'network'     => $network,
            'processes'   => $processes
        ];
    }
    
    public function networkIndex(): View
    {
        // Chúng ta vẫn lấy data từ hàm getServerMetrics có sẵn để hiển thị ban đầu
        $data = $this->getServerMetrics(); 
        return view('network', $data);
    }

    public function handleCommand(Request $request) 
    {
        $rawCommand = $request->input('command');
        $command = strtolower(trim($rawCommand));
        $reply = "";

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
                    // Rút gọn lại vì đã use App\Models\ServerMetric; ở trên
                    $logs = ServerMetric::orderBy('created_at', 'desc')->take(5)->get();
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
            $reply = "🤖 Hiện tại tính năng AI đang tạm đóng. Các lệnh hỗ trợ: 'history cpu', 'clear cache', 'stop attack'.";
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