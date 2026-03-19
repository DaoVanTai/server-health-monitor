<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ServerMetric;

class CollectServerMetrics extends Command
{
    // Tên câu lệnh để bạn gọi con robot này chạy
    protected $signature = 'metrics:collect';

    // Mô tả công việc của nó
    protected $description = 'Thu thập thông số CPU, RAM, DISK và lưu vào Database';

    public function handle()
    {
        // 1. Lấy % CPU (Dùng lệnh top của Ubuntu/aaPanel)
        // Lấy tổng của %us (user) và %sy (system)
        $cpuOutput = shell_exec("top -bn1 | grep '%Cpu(s)' | awk '{print $2 + $4}'");
        $cpuPercent = $cpuOutput ? (float) trim($cpuOutput) : 0;

        // 2. Lấy % RAM (Dùng lệnh free)
        $ramOutput = shell_exec("free | awk '/Mem/ {printf(\"%.2f\", $3/$2 * 100)}'");
        $ramPercent = $ramOutput ? (float) trim($ramOutput) : 0;

        // 3. Lấy % DISK (Của phân vùng gốc '/')
        $diskOutput = shell_exec("df / | awk 'NR==2 {print $5}' | tr -d '%'");
        $diskPercent = $diskOutput ? (float) trim($diskOutput) : 0;

        // 4. Bơm dữ liệu vào Database thông qua Model của bạn
        ServerMetric::create([
            'cpu_percent'  => $cpuPercent,
            'ram_percent'  => $ramPercent,
            'disk_percent' => $diskPercent,
        ]);

        // In ra màn hình dòng chữ xanh lá cây báo cáo thành công
        $this->info("✅ Đã lưu thông số thành công lúc " . now()->format('H:i:s d/m/Y'));
    }
}