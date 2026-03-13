<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ServerMetric; // Kết nối với bảng Database

class LogServerMetrics extends Command
{
    // Tên lệnh để chạy trong Terminal
    protected $signature = 'monitor:log';

    // Mô tả lệnh
    protected $description = 'Tự động lấy thông số CPU, RAM, Disk của Ubuntu và lưu vào Database';

    public function handle()
    {
        // 1. Đo CPU
        $cpuLoad = sys_getloadavg();
        $cpuPercent = min(round($cpuLoad[0] * 20, 2), 100); 

        // 2. Đo RAM
        $free = shell_exec('free -m');
        preg_match('/Mem:\s+(\d+)\s+(\d+)\s+(\d+)/', $free, $mem);
        $ramTotal = isset($mem[1]) ? round($mem[1] / 1024, 2) : 2.0;
        $ramUsed = isset($mem[2]) ? round($mem[2] / 1024, 2) : 1.0;
        $ramPercent = $ramTotal > 0 ? round(($ramUsed / $ramTotal) * 100, 2) : 0;

        // 3. Đo Ổ cứng
        $diskTotal = disk_total_space('/');
        $diskFree = disk_free_space('/');
        $diskUsed = $diskTotal - $diskFree;
        $diskPercent = $diskTotal > 0 ? round(($diskUsed / $diskTotal) * 100, 2) : 0;

        // 4. Lưu tất cả vào Database
        ServerMetric::create([
            'cpu_percent' => $cpuPercent,
            'ram_percent' => $ramPercent,
            'disk_percent' => $diskPercent
        ]);

        // Thông báo ra Terminal
        $this->info('✅ Đã ghi log hệ thống thành công lúc ' . date('H:i:s'));
    }
}