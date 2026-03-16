<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use Illuminate\Support\Facades\DB;

class Kernel extends ConsoleKernel
{
    /**
     * Định nghĩa lịch trình chạy tự động của ứng dụng.
     */
    protected function schedule(Schedule $schedule): void
    {
        // Lên lịch chạy mỗi phút một lần
        $schedule->call(function () {
            
            // --- 1. LẤY THÔNG SỐ CPU TỔNG QUÁT ---
            $load = sys_getloadavg();
            $cpuLoad = $load ? $load[0] * 100 : 0;

            // --- 2. LẤY THÔNG SỐ RAM TỔNG QUÁT ---
            $memInfo = @file_get_contents("/proc/meminfo");
            $ramPercent = 0;
            if ($memInfo) {
                preg_match('/MemTotal:\s+(\d+)/', $memInfo, $totalMatches);
                preg_match('/MemAvailable:\s+(\d+)/', $memInfo, $availableMatches);
                $total = $totalMatches[1] ?? 0;
                $available = $availableMatches[1] ?? 0;
                $ramPercent = $total > 0 ? round((($total - $available) / $total) * 100, 2) : 0;
            }

            // --- 3. LẤY THÔNG SỐ Ổ CỨNG ---
            $totalD = disk_total_space(".");
            $freeD = disk_free_space(".");
            $diskPercent = $totalD > 0 ? round((($totalD - $freeD) / $totalD) * 100, 2) : 0;

            // --- 4. LẤY DỮ LIỆU NETWORK (MB) ---
            $netStats = @file("/proc/net/dev");
            $netIn = 0; $netOut = 0;
            if ($netStats) {
                foreach ($netStats as $line) {
                    if (preg_match('/(eth0|ens33|enp|wlan)/', $line)) {
                        $info = preg_split('/\s+/', trim($line));
                        $netIn += round($info[1] / 1024 / 1024, 2); 
                        $netOut += round($info[9] / 1024 / 1024, 2);
                    }
                }
            }

            // --- 5. LƯU VÀO BẢNG CHÍNH (ServerMetric) ---
            \App\Models\ServerMetric::create([
                'cpu_percent' => $cpuLoad,
                'ram_percent' => $ramPercent,
                'disk_percent' => $diskPercent,
                'network_in'   => $netIn,
                'network_out'  => $netOut,
            ]);

            // --- 6. LẤY TOP 5 TÁC VỤ VÀ LƯU VÀO BẢNG (ProcessLog) ---
            // Lấy PID, Tên tiến trình, %CPU, %RAM
            exec("ps -eo pid,comm,%cpu,%mem --sort=-%cpu | head -n 6", $output);
            if (count($output) > 1) {
                foreach (array_slice($output, 1) as $line) {
                    $data = preg_split('/\s+/', trim($line));
                    if (count($data) >= 4) {
                        \App\Models\ProcessLog::create([
                            'pid'          => $data[0],
                            'process_name' => $data[1],
                            'cpu_usage'    => $data[2],
                            'ram_usage'    => $data[3],
                        ]);
                    }
                }
            }
        })->everyMinute();
    }

    /**
     * Đăng ký các command cho ứng dụng.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');
        require base_path('routes/console.php');
    }
}