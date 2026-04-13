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
}