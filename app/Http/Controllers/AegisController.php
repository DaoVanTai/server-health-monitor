<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AegisController extends Controller
{
    // Thêm tham số Request để nhận lệnh từ người dùng
    public function index(Request $request) 
    {
        // =========================================================
        // PHẦN 1: LOGIC TÍNH ĐIỂM SỨC KHỎE (GIỮ NGUYÊN TỪ BẢN CŨ)
        // =========================================================
        
        $recentMetrics = DB::table('server_metrics')
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        $avgCpu = $recentMetrics->avg('cpu_percent') ?? 0;
        $avgRam = $recentMetrics->avg('ram_percent') ?? 0;
        $healthScore = 100 - ($avgCpu * 0.5) - ($avgRam * 0.3);
        $threatCount = DB::table('blacklists')->count();
        $isSpiking = $avgCpu > 80 ? true : false;

        $insights = [
            "> [SYSTEM] Aegis Neural Core v3.0.5 initialized...",
            "> [INFO] Connecting to local database 'server-health'..."
        ];

        if ($isSpiking) {
            $insights[] = "> ALERT: Phát hiện CPU Spike bất thường (" . round($avgCpu, 1) . "%).";
        }
        if ($avgRam > 85) {
            $insights[] = "> WARNING: RAM đang chạm ngưỡng tới hạn. Đề xuất giải phóng cache.";
        }
        $insights[] = "> STATUS: Hệ thống đang vận hành với " . $threatCount . " quy tắc tường lửa.";


        // =========================================================
        // PHẦN 2: TƯƠNG TÁC AI & VẼ BIỂU ĐỒ (TÍNH NĂNG MỚI)
        // =========================================================
        
        $chartData = null; // Khởi tạo biến biểu đồ rỗng
        $aiResponse = "> Chờ lệnh từ quản trị viên...";

        // Kiểm tra xem người dùng có gõ lệnh vào ô Chat không
        if ($request->has('ai_command')) {
            $command = strtolower($request->get('ai_command'));
            $aiResponse = "> AEGIS: Đang phân tích yêu cầu: '$command'...";

            // Lấy dữ liệu 24h qua từ Database để vẽ biểu đồ
            $historyData = DB::table('server_metrics')
                ->where('created_at', '>=', now()->subDay())
                ->orderBy('created_at', 'asc') // Sắp xếp từ cũ đến mới để vẽ trục X
                ->get();

            // Nhận diện từ khóa "biểu đồ cpu"
            if (str_contains($command, 'biểu đồ cpu')) {
                $chartData = [
                    'label' => 'Mức sử dụng CPU (%)',
                    'labels' => $historyData->pluck('created_at')->map(fn($t) => date('H:i', strtotime($t)))->toArray(),
                    'values' => $historyData->pluck('cpu_percent')->toArray(),
                    'color' => '#22d3ee' // Màu Neon Cyan
                ];
                $aiResponse = "> Aegis: Đã trích xuất dữ liệu CPU 24h qua. Đang xuất biểu đồ...";
            } 
            // Nhận diện từ khóa "biểu đồ ram"
            elseif (str_contains($command, 'biểu đồ ram')) {
                $chartData = [
                    'label' => 'Mức sử dụng RAM (%)',
                    'labels' => $historyData->pluck('created_at')->map(fn($t) => date('H:i', strtotime($t)))->toArray(),
                    'values' => $historyData->pluck('ram_percent')->toArray(),
                    'color' => '#a855f7' // Màu Neon Purple
                ];
                $aiResponse = "> Aegis: Đã trích xuất dữ liệu RAM 24h qua. Đang xuất biểu đồ...";
            } 
            // Nếu gõ sai lệnh
            else {
                $aiResponse = "> Aegis: Lệnh không hợp lệ. Vui lòng gõ 'vẽ biểu đồ cpu' hoặc 'vẽ biểu đồ ram'.";
            }
        }

        // =========================================================
        // PHẦN 3: GỬI DỮ LIỆU SANG GIAO DIỆN BLADE
        // =========================================================
        
        return view('ai_intelligence', [
            'score' => round($healthScore),
            'threats' => $threatCount,
            'insights' => $insights,
            'avgCpu' => round($avgCpu, 1),
            'chartData' => $chartData,     // Dữ liệu mảng để thư viện Chart.js vẽ
            'aiResponse' => $aiResponse    // Câu trả lời của AI
        ]);
    }
}