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
        // PHẦN 2: BỘ NÃO LOCAL (PHÂN TÍCH TỪ KHÓA - KHÔNG VẼ BIỂU ĐỒ)
        // =========================================================
        // Giữ biến chartData = null để giao diện Blade không bị lỗi khi tìm biến này
        $chartData = null; 
        $aiResponse = "> Chờ lệnh từ quản trị viên...";

        if ($request->has('ai_command')) {
            $command = mb_strtolower(trim($request->get('ai_command')), 'UTF-8');

            // 1. Nhóm câu hỏi về CHÀO HỎI
            if (str_contains($command, 'chào') || str_contains($command, 'hello') || str_contains($command, 'aegis')) {
                $aiResponse = "> Aegis: Xin chào Quản trị viên. Tôi là Aegis, AI giám sát độc quyền của hệ thống Server Health Monitoring. Hiện tại các luồng dữ liệu đang được theo dõi sát sao. Tôi có thể giúp gì cho bạn trong ca trực hôm nay?";
            }
            // 2. Nhóm câu hỏi về TẤN CÔNG / BẢO MẬT (LẤY SỐ LIỆU THẬT TỪ FIREWALL)
            elseif (str_contains($command, 'tấn công') || str_contains($command, 'hacker') || str_contains($command, 'ddos') || str_contains($command, 'bị cấm') || str_contains($command, 'bảo mật')) {
                if ($threatCount > 0) {
                    $aiResponse = "> Aegis: [BÁO CÁO AN NINH] Hệ thống phòng thủ đang hoạt động mức cao. Đã phát hiện và khóa thành công {$threatCount} địa chỉ IP có hành vi Brute-force/DDoS. Các cổng dịch vụ trọng yếu vẫn an toàn và không bị gián đoạn.";
                } else {
                    $aiResponse = "> Aegis: [BÁO CÁO AN NINH] Không gian mạng hiện tại hoàn toàn sạch. Tường lửa chưa ghi nhận địa chỉ IP nào có hành vi rà quét trái phép (Blacklist: 0). Tôi vẫn đang tiếp tục trực ban 24/7.";
                }
            }
            // 3. Nhóm câu hỏi về CPU
            elseif (str_contains($command, 'cpu') || str_contains($command, 'vi xử lý') || str_contains($command, 'quá tải')) {
                $cpuStatus = $avgCpu > 80 ? 'CẢNH BÁO MỨC TẢI CAO' : 'TỐT';
                $aiResponse = "> Aegis: [PHÂN TÍCH CPU] Mức độ chiếm dụng vi xử lý trung bình đang ở mức " . round($avgCpu, 1) . "%. Trạng thái: {$cpuStatus}. Các tiến trình lõi của hệ điều hành vẫn đang được phân bổ hợp lý.";
            }
            // 4. Nhóm câu hỏi về RAM / BỘ NHỚ
            elseif (str_contains($command, 'ram') || str_contains($command, 'bộ nhớ') || str_contains($command, 'memory')) {
                $ramStatus = $avgRam > 85 ? 'SẮP TRÀN BỘ NHỚ' : 'ỔN ĐỊNH';
                $aiResponse = "> Aegis: [PHÂN TÍCH RAM] Mức tiêu thụ RAM hiện tại là " . round($avgRam, 1) . "%. Trạng thái: {$ramStatus}. Tài nguyên bộ nhớ vẫn đủ khả năng đáp ứng các truy vấn từ Database và request của người dùng.";
            }
            // 5. Nhóm câu hỏi về ĐIỂM SỨC KHỎE TỔNG THỂ
            elseif (str_contains($command, 'sức khỏe') || str_contains($command, 'tổng thể') || str_contains($command, 'status') || str_contains($command, 'tình trạng')) {
                $aiResponse = "> Aegis: [KIỂM TRA HỆ THỐNG] Điểm sức khỏe Health Score đạt " . round($healthScore) . "/100. Thông số chi tiết: CPU (" . round($avgCpu, 1) . "%), RAM (" . round($avgRam, 1) . "%), Số lượng hiểm họa đã chặn ({$threatCount}). Máy chủ đang vận hành trơn tru.";
            }
            // 6. Nhóm câu hỏi về THÔNG TIN DỰ ÁN (DÙNG ĐỂ DEMO CHO GIÁO VIÊN)
            elseif (str_contains($command, 'đồ án') || str_contains($command, 'hệ thống này làm gì') || str_contains($command, 'chức năng') || str_contains($command, 'dự án')) {
                $aiResponse = "> Aegis: Đây là hệ thống Server Health Monitoring and Detection. Nhiệm vụ cốt lõi của tôi là giám sát tài nguyên phần cứng theo thời gian thực và tự động nhận diện, chặn đứng các cuộc tấn công mạng. Dữ liệu được thu thập liên tục để đảm bảo server luôn sống sót trước mọi rủi ro.";
            }
            // MẶC ĐỊNH LỆNH KHÔNG HỢP LỆ
            else {
                $aiResponse = "> Aegis: [LỖI NGỮ NGHĨA] Mã lệnh không hợp lệ. Vui lòng sử dụng các từ khóa kiểm tra như: 'tình trạng CPU', 'thông số RAM', 'báo cáo hacker', 'kiểm tra sức khỏe tổng thể' hoặc 'tổng quan dự án'.";
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
            'chartData' => clone $chartData ?? null,
            'aiResponse' => $aiResponse 
        ]);
    }
}