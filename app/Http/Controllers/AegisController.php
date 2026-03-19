<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AegisController extends Controller
{
    public function index()
    {
        // 1. Lấy dữ liệu 6h qua để AI phân tích
        $recentMetrics = DB::table('server_metrics')
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        // 2. Logic AI: Tính toán điểm sức khỏe (Score)
        $avgCpu = $recentMetrics->avg('cpu_percent') ?? 0;
        $avgRam = $recentMetrics->avg('ram_percent') ?? 0;
        $healthScore = 100 - ($avgCpu * 0.5) - ($avgRam * 0.3);

        // 3. Logic AI: Phát hiện đe dọa (Threat Detection)
        $threatCount = DB::table('blacklists')->count();
        $isSpiking = $avgCpu > 80 ? true : false;

        // 4. Tạo các câu nhận xét AI (Dynamic Insights)
        $insights = [];
        if ($isSpiking) {
            $insights[] = "> ALERT: Phát hiện CPU Spike bất thường ($avgCpu%).";
        }
        if ($avgRam > 85) {
            $insights[] = "> WARNING: RAM đang chạm ngưỡng tới hạn. Đề xuất giải phóng cache.";
        }
        $insights[] = "> STATUS: Hệ thống đang vận hành với " . $threatCount . " quy tắc tường lửa.";

        return view(' ai_intelligence', [
            'score' => round($healthScore),
            'threats' => $threatCount,
            'insights' => $insights,
            'avgCpu' => round($avgCpu, 1)
        ]);
    }
}