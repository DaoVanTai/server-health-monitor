<?php

namespace App\Http\Controllers;

use App\Models\ServerMetric;
use Illuminate\Http\Request;

class MetricController extends Controller
{
    public function captureRealData()
    {
        // 1. ĐO TẢI CPU THỰC TẾ
        $cpuLoad = sys_getloadavg()[0]; // Tải trung bình trong 1 phút qua
        $cpuCores = (int) shell_exec('nproc'); // Đếm số nhân CPU của máy chủ
        if ($cpuCores === 0) $cpuCores = 1; // Đề phòng lỗi chia cho 0
        
        $cpuPercent = round(($cpuLoad / $cpuCores) * 100, 2);
        if ($cpuPercent > 100) $cpuPercent = 100; // Giới hạn mức trần 100%

        // 2. ĐO RAM THỰC TẾ (Sử dụng lệnh hệ thống Linux)
        $ramCommand = "free | awk '/Mem/{printf(\"%.2f\"), $3/$2*100}'";
        $ramPercent = (float) shell_exec($ramCommand);

        // 3. ĐO Ổ CỨNG THỰC TẾ
        $diskTotal = disk_total_space('/'); // Đọc dung lượng tổng của phân vùng gốc
        $diskFree = disk_free_space('/');   // Đọc dung lượng còn trống
        $diskPercent = round((($diskTotal - $diskFree) / $diskTotal) * 100, 2);

        // 4. LƯU VÀO DATABASE (Khớp 100% với tên cột của bạn)
        $metric = ServerMetric::create([
            'cpu_percent' => $cpuPercent,
            'ram_percent' => $ramPercent,
            'disk_percent' => $diskPercent,
        ]);

        // 5. TRẢ VỀ KẾT QUẢ JSON (Để kiểm tra trực tiếp trên trình duyệt)
        return response()->json([
            'status' => 'success',
            'message' => 'Lấy thông số thực tế từ máy chủ Ubuntu thành công!',
            'data' => $metric
        ]);
    }
}