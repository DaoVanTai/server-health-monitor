<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AegisController extends Controller
{
    public function index(Request $request) 
    {
        // =========================================================
        // PHẦN 1: LOGIC TÍNH ĐIỂM SỨC KHỎE (DỮ LIỆU THẬT TỪ DB)
        // =========================================================
        $recentMetrics = DB::table('server_metrics')
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        $avgCpu = $recentMetrics->avg('cpu_percent') ?? 0;
        $avgRam = $recentMetrics->avg('ram_percent') ?? 0;
        $healthScore = max(0, 100 - ($avgCpu * 0.5) - ($avgRam * 0.3));
        $threatCount = DB::table('blacklists')->count();
        $isSpiking = $avgCpu > 80 ? true : false;

        $insights = [
            "> [SYSTEM] Aegis Neural Core v4.0 (Local Intelligence) active...",
            "> [INFO] Giao thức mạng khép kín. Đang đọc dữ liệu từ Database nội bộ..."
        ];

        if ($isSpiking) {
            $insights[] = "> ALERT: Phát hiện CPU Spike bất thường (" . round($avgCpu, 1) . "%).";
        }
        if ($avgRam > 85) {
            $insights[] = "> WARNING: RAM đang chạm ngưỡng tới hạn. Đề xuất giải phóng cache.";
        }
        $insights[] = "> STATUS: Hệ thống đang vận hành với " . $threatCount . " quy tắc tường lửa.";


        // =========================================================
        // PHẦN 2: BỘ NÃO LOCAL (PHÂN TÍCH TỪ KHÓA - KHÔNG CẦN INTERNET)
        // =========================================================
        $chartData = null; 
        $aiResponse = "> Chờ lệnh từ quản trị viên...";

        if ($request->has('ai_command')) {
            // Lấy câu lệnh và chuyển thành chữ thường để dễ nhận diện
            $command = mb_strtolower(trim($request->get('ai_command')), 'UTF-8');
            
            $historyData = DB::table('server_metrics')
                ->where('created_at', '>=', now()->subDay())
                ->orderBy('created_at', 'asc') 
                ->get();

            // 1. Nhóm câu hỏi về CHÀO HỎI
            if (str_contains($command, 'chào') || str_contains($command, 'hello')) {
                $aiResponse = "> Aegis: Xin chào Quản trị viên. Hệ thống phòng thủ đang hoạt động ổn định. Bạn muốn kiểm tra CPU, RAM hay Tình trạng tường lửa?";
            }
            // 2. Nhóm câu hỏi về TẤN CÔNG / FIREWALL
            elseif (str_contains($command, 'tấn công') || str_contains($command, 'hacker') || str_contains($command, 'ddos') || str_contains($command, 'bị cấm')) {
                $aiResponse = "> Aegis: Báo cáo an ninh: Tường lửa tự động đang được kích hoạt. Tính đến nay, hệ thống đã phát hiện và khóa vĩnh viễn {$threatCount} địa chỉ IP có hành vi rà quét/DDoS. Server hiện tại an toàn tuyệt đối.";
            }
            // 3. Nhóm câu hỏi về CPU (CÓ VẼ BIỂU ĐỒ)
            elseif (str_contains($command, 'cpu') || str_contains($command, 'hiệu suất') || str_contains($command, 'quá tải')) {
                $aiResponse = "> Aegis: Mức tải CPU hiện tại đang ở mức " . round($avgCpu, 1) . "%. Điểm sức khỏe tổng thể: " . round($healthScore) . "/100. Đang trích xuất biểu đồ phân tích thời gian thực...";
                $chartData = [
                    'label' => 'Mức sử dụng CPU (%)',
                    'labels' => $historyData->pluck('created_at')->map(fn($t) => date('H:i', strtotime($t)))->toArray(),
                    'values' => $historyData->pluck('cpu_percent')->toArray(),
                    'color' => '#22d3ee' 
                ];
            }
            // 4. Nhóm câu hỏi về RAM (CÓ VẼ BIỂU ĐỒ)
            elseif (str_contains($command, 'ram') || str_contains($command, 'bộ nhớ')) {
                $aiResponse = "> Aegis: Không gian bộ nhớ RAM đang tiêu thụ " . round($avgRam, 1) . "%. Các tiến trình nền hoạt động ổn định. Đang xuất biểu đồ phân bổ RAM...";
                $chartData = [
                    'label' => 'Mức sử dụng RAM (%)',
                    'labels' => $historyData->pluck('created_at')->map(fn($t) => date('H:i', strtotime($t)))->toArray(),
                    'values' => $historyData->pluck('ram_percent')->toArray(),
                    'color' => '#a855f7' 
                ];
            }
            // 5. Nhóm câu hỏi về THÔNG TIN ĐỒ ÁN
            elseif (str_contains($command, 'đồ án') || str_contains($command, 'hệ thống này làm gì') || str_contains($command, 'chức năng')) {
                $aiResponse = "> Aegis: Đây là hệ thống 'Server Health Monitoring and Detection'. Cấu trúc gồm 3 module chính: Giám sát tài nguyên (CPU/RAM realtime), Tường lửa thông minh (tự động ban IP khi có dấu hiệu DDoS), và Trung tâm tình báo AI hỗ trợ phân tích dữ liệu.";
            }
            // 6. Nhóm câu hỏi về MẠNG / NETWORK
            elseif (str_contains($command, 'mạng') || str_contains($command, 'network') || str_contains($command, 'truy cập')) {
                $aiResponse = "> Aegis: Phân tích luồng mạng: Đang lắng nghe trên các cổng 80 và 443. Không phát hiện lưu lượng truy cập bất thường (Flood). Máy chủ Nginx/Apache phản hồi tốt.";
            }
            // MẶC ĐỊNH KHÔNG HIỂU
            else {
                $aiResponse = "> Aegis: Lệnh chưa được định dạng chuẩn. Vui lòng thử các từ khóa: 'tình trạng CPU', 'biểu đồ RAM', 'có ai tấn công không', hoặc 'tổng quan đồ án'.";
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
            'chartData' => $chartData,
            'aiResponse' => $aiResponse 
        ]);
    }
}