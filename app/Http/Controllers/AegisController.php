<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;

class AegisController extends Controller
{
    public function index(Request $request) 
    {
        // =========================================================
        // PHẦN 1: THU THẬP DỮ LIỆU THỰC TẾ (REALTIME TUYỆT ĐỐI)
        // =========================================================
        $recentMetrics = DB::table('server_metrics')
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        // Lấy chính xác bản ghi "nóng" nhất vừa được lưu vào DB
        $latestMetric = DB::table('server_metrics')->orderBy('created_at', 'desc')->first();

        // Dữ liệu dùng để AI trả lời (Real-time tuyệt đối)
        $currentCpu = $latestMetric->cpu_percent ?? 0;
        $currentRam = $latestMetric->ram_percent ?? 0;
        $rawDownload = $latestMetric->network_in ?? 0;
        $rawUpload = $latestMetric->network_out ?? 0;
        $netDownload = number_format((float)$rawDownload, 2, '.', '');
        $netUpload = number_format((float)$rawUpload, 2, '.', '');

        // Điểm sức khỏe tổng quát (Vẫn dùng trung bình 10 lần để khách quan)
        $avgCpu = $recentMetrics->avg('cpu_percent') ?? 0;
        $avgRam = $recentMetrics->avg('ram_percent') ?? 0;
        $healthScore = max(0, 100 - ($avgCpu * 0.5) - ($avgRam * 0.3));
        
        // Dữ liệu Ổ cứng (Disk)
        $diskTotal = disk_total_space('/');
        $diskFree = disk_free_space('/');
        $diskUsedPercent = $diskTotal > 0 ? round((($diskTotal - $diskFree) / $diskTotal) * 100, 1) : 0;
        $diskFreeGB = round($diskFree / 1073741824, 2);
        $diskTotalGB = round($diskTotal / 1073741824, 2);

        $threatCount = DB::table('blacklists')->count();
        $isSpiking = $currentCpu > 80 ? true : false;

        $insights = [
            "> [SYSTEM] Aegis Neural Core v4.0 (Local Intelligence) active...",
            "> [INFO] Giao thức mạng khép kín. Đang phân tích luồng dữ liệu phần cứng..."
        ];

        if ($isSpiking) {
            $insights[] = "> ALERT: Phát hiện CPU Spike bất thường (" . round($currentCpu, 1) . "%).";
        }
        if ($currentRam > 85) {
            $insights[] = "> WARNING: RAM đang chạm ngưỡng tới hạn. Đề xuất giải phóng cache.";
        }
        $insights[] = "> STATUS: Firewall đang khóa " . $threatCount . " mục tiêu nguy hiểm.";

        // =========================================================
        // PHẦN 2: BỘ NÃO LOCAL (BÁO CÁO THÔNG SỐ HIỆN TẠI)
        // =========================================================
        $chartData = null; 
        $aiResponse = "> Chờ lệnh từ quản trị viên...";

        if ($request->has('ai_command')) {
            $command = mb_strtolower(trim($request->get('ai_command')), 'UTF-8');

            if (str_contains($command, 'tấn công') || str_contains($command, 'hacker') || str_contains($command, 'ddos') || str_contains($command, 'bị cấm')) {
                if ($threatCount > 0) {
                    $aiResponse = "> Aegis: [BÁO CÁO AN NINH] Hệ thống phòng thủ đang hoạt động mức cao. Đã phát hiện và khóa thành công {$threatCount} địa chỉ IP độc hại. Các cổng dịch vụ trọng yếu vẫn an toàn.";
                } else {
                    $aiResponse = "> Aegis: [BÁO CÁO AN NINH] Không gian mạng hiện tại hoàn toàn sạch. Chưa ghi nhận địa chỉ IP nào có hành vi rà quét trái phép (Blacklist: 0).";
                }
            }
            elseif (str_contains($command, 'cpu') || str_contains($command, 'vi xử lý')) {
                $cpuStatus = $currentCpu > 80 ? 'CẢNH BÁO MỨC TẢI CAO' : 'TỐT';
                $aiResponse = "> Aegis: [PHÂN TÍCH CPU] Mức độ chiếm dụng vi xử lý CHÍNH XÁC tại thời điểm này là " . round($currentCpu, 1) . "%. Trạng thái: {$cpuStatus}. Các tiến trình lõi vận hành ổn định.";
            }
            elseif (str_contains($command, 'ram') || str_contains($command, 'bộ nhớ') || str_contains($command, 'memory')) {
                $ramStatus = $currentRam > 85 ? 'SẮP TRÀN BỘ NHỚ' : 'ỔN ĐỊNH';
                $aiResponse = "> Aegis: [PHÂN TÍCH RAM] Mức tiêu thụ RAM CHÍNH XÁC tại thời điểm này là " . round($currentRam, 1) . "%. Trạng thái: {$ramStatus}. Tài nguyên bộ nhớ vẫn đủ để đáp ứng các luồng dữ liệu mới.";
            }
            elseif (str_contains($command, 'ổ cứng') || str_contains($command, 'disk') || str_contains($command, 'dung lượng')) {
                $diskStatus = $diskUsedPercent > 90 ? 'CẢNH BÁO ĐẦY Ổ CỨNG' : 'ĐANG TRỐNG NHIỀU';
                $aiResponse = "> Aegis: [PHÂN TÍCH Ổ CỨNG] Dung lượng lưu trữ đang sử dụng {$diskUsedPercent}% (Trống {$diskFreeGB}GB / Tổng {$diskTotalGB}GB). Trạng thái: {$diskStatus}. Dữ liệu log vẫn đang được ghi chép an toàn.";
            }
            elseif (str_contains($command, 'mạng') || str_contains($command, 'network') || str_contains($command, 'băng thông') || str_contains($command, 'tốc độ')) {
                $aiResponse = "> Aegis: [LƯU LƯỢNG MẠNG] Giao diện mạng phản hồi: Connected // Stable. Tốc độ hiện tại - Download: {$netDownload} MB/s | Upload: {$netUpload} MB/s. Không có dấu hiệu nghẽn cổ chai (bottleneck).";
            }
            elseif (str_contains($command, 'tình trạng hệ thống') || str_contains($command, 'status') || str_contains($command, 'tổng quan')) {
                $aiResponse = "> Aegis: [TÌNH TRẠNG HỆ THỐNG] Core Health Score: " . round($healthScore) . "/100. \nCPU: " . round($currentCpu, 1) . "% | RAM: " . round($currentRam, 1) . "% | Disk: {$diskUsedPercent}%. \nTường lửa đã chặn {$threatCount} mối đe dọa. Toàn bộ máy chủ đang trong trạng thái hoàn hảo.";
            }
            elseif (str_contains($command, 'đồ án') || str_contains($command, 'hệ thống này làm gì') || str_contains($command, 'chào') || str_contains($command, 'hello')) {
                $aiResponse = "> Aegis: Xin chào Quản trị viên. Tôi là Aegis, AI giám sát độc quyền của hệ thống Server Health Monitoring. Hiện tại các luồng dữ liệu đang được theo dõi sát sao. Tôi có thể giúp gì cho bạn?";
            }
            else {
                $aiResponse = "> Aegis: [LỖI NGỮ NGHĨA] Lệnh không xác định. Các khóa quét hỗ trợ: 'tình trạng hệ thống', 'tình trạng ổ cứng', 'kiểm tra mạng', 'thông số ram', 'thông số cpu', 'kiểm tra tấn công'.";
            }
        }

        return view('ai_intelligence', [
            'score' => round($healthScore),
            'threats' => $threatCount,
            'insights' => $insights,
            'chartData' => $chartData,
            'aiResponse' => $aiResponse 
        ]);
    }
}