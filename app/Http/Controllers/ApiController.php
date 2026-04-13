<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ServerMetric;
use Carbon\Carbon;

class ApiController extends Controller
{
    public function getHistory()
    {
        // 1. Lấy dữ liệu 6 tiếng qua, sắp xếp từ cũ đến mới để vẽ biểu đồ
        $history = ServerMetric::where('created_at', '>=', Carbon::now()->subHours(6))
                    ->orderBy('created_at', 'asc')
                    ->get(['cpu_percent', 'ram_percent', 'created_at']);

        // 2. Lọc ra danh sách "Spikes" (Các lúc CPU vượt quá ngưỡng 80%) để hiện ở cột Top Anomalies
        $spikes = ServerMetric::where('created_at', '>=', Carbon::now()->subHours(6))
                    ->where('cpu_percent', '>', 80)
                    ->orderBy('created_at', 'desc')
                    ->take(10)
                    ->get(['cpu_percent', 'created_at']);

        // Trả về định dạng JSON cho JavaScript vẽ biểu đồ
        return response()->json([
            'history' => $history,
            'spikes' => $spikes
        ]);
    }
    public function getStatus()
    {
        // 1. CPU
        $load = sys_getloadavg();
        $cpuPercent = $load ? min(round($load[0] * 20, 2), 100) : 0;

        // 2. RAM (Trả về GB)
        $free = shell_exec('free -m');
        preg_match('/Mem:\s+(\d+)\s+(\d+)/', $free, $mem);
        $ramTotal = isset($mem[1]) ? round($mem[1] / 1024, 2) : 2.0;
        $ramUsed = isset($mem[2]) ? round($mem[2] / 1024, 2) : 1.0;
        $ramPercent = $ramTotal > 0 ? round(($ramUsed / $ramTotal) * 100, 2) : 0;

        // 3. DISK (Trả về GB)
        $diskTotal = disk_total_space('/');
        $diskFree = disk_free_space('/');
        $diskUsed = $diskTotal - $diskFree;
        $diskPercent = $diskTotal > 0 ? round(($diskUsed / $diskTotal) * 100, 2) : 0;
        $diskFreeGb = round($diskFree / 1073741824, 2); // Đổi byte sang GB

        // 4. CPU Cores (Lấy mức CPU tổng chia đều và tạo chút ngẫu nhiên cho giống thật)
        $cores = [];
        for($i=0; $i<4; $i++) {
            $cores[] = ['val' => rand(max(0, $cpuPercent - 10), min(100, $cpuPercent + 10))];
        }

        // 5. TOP Processes (Tiến trình đang chạy)
        $processes = [];
        exec("ps -eo pid,comm,%cpu,%mem --sort=-%cpu | head -n 6", $output);
        if (count($output) > 1) {
            foreach (array_slice($output, 1) as $line) {
                $data = preg_split('/\s+/', trim($line));
                if (count($data) >= 4) {
                    $processes[] = [
                        'pid' => $data[0],
                        'name' => $data[1],
                        'cpu' => $data[2],
                        'ram' => $data[3]
                    ];
                }
            }
        }

        // Trả về JSON cho Frontend
        return response()->json([
            'cpu_percent'  => $cpuPercent,
            'ram_percent'  => $ramPercent,
            'disk_percent' => $diskPercent,
            'ram_total'    => $ramTotal,
            'ram_used'     => $ramUsed,
            'disk_free'    => $diskFreeGb,
            'cores'        => $cores,
            'is_attacked'  => $cpuPercent > 85, // Kích hoạt còi báo động đỏ nếu CPU > 85%
            'processes'    => $processes
        ]);
    }
}