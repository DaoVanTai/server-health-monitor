<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AegisController extends Controller
{
    public function index(Request $request) 
    {
        // =========================================================
        // PHẦN 1: THU THẬP DỮ LIỆU THỰC TẾ (REALTIME)
        // =========================================================
        $recentMetrics = DB::table('server_metrics')
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        $latestMetric = $recentMetrics->first();

        // 1. Dữ liệu CPU & RAM
        $avgCpu = $recentMetrics->avg('cpu_percent') ?? 0;
        $avgRam = $recentMetrics->avg('ram_percent') ?? 0;

        // 2. Dữ liệu Ổ cứng (Disk) - Lấy trực tiếp từ phần cứng vật lý
        $diskTotal = disk_total_space('/');
        $diskFree = disk_free_space('/');
        $diskUsedPercent = $diskTotal > 0 ? round((($diskTotal - $diskFree) / $diskTotal) * 100, 1) : 0;
        $diskFreeGB = round($diskFree / 1073741824, 2);
        $diskTotalGB = round($diskTotal / 1073741824, 2);

        // 3. Dữ liệu Mạng (Network) - Quét tự động tên cột DB & Format 2 số thập phân giống Dashboard
        $metricData = $latestMetric ? (array) $latestMetric : [];
        $rawDownload = $metricData['download_speed'] ?? $metricData['network_rx'] ?? $metricData['download'] ?? $metricData['rx_speed'] ?? $metricData['rx'] ?? 0;
        $rawUpload = $metricData['upload_speed'] ?? $metricData['network_tx'] ?? $metricData['upload'] ?? $metricData['tx_speed'] ?? $metricData['tx'] ?? 0;
        
        $netDownload = number_format((float)$rawDownload, 2, '.', '');
        $netUpload = number_format((float)$rawUpload, 2, '.', '');

        // 4. Dữ liệu Tường lửa & Sức khỏe
        $healthScore = max(0, 100 - ($avgCpu * 0.5) - ($avgRam * 0.3));
        $threatCount = DB::table('blacklists')->count();
        $isSpiking = $avgCpu > 80 ? true : false;

        $insights = [
            "> [SYSTEM] Aegis Neural Core v4.0 (Local Intelligence) active...",
            "> [INFO] Giao thức mạng khép kín. Đang phân tích luồng dữ liệu phần cứng..."
        ];

        if ($isSpiking) {
            $insights[] = "> ALERT: Phát hiện CPU Spike bất thường (" . round($avgCpu, 1) . "%).";
        }
        if ($avgRam > 85) {
            $insights[] = "> WARNING: RAM đang chạm ngưỡng tới hạn. Đề xuất giải phóng cache.";
        }
        $insights[] = "> STATUS: Firewall đang khóa " . $threatCount . " mục tiêu nguy hiểm.";

        // =========================================================
        // PHẦN 2: BỘ NÃO LOCAL (PHÂN TÍCH TỪ KHÓA)
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
                $cpuStatus = $avgCpu > 80 ? 'CẢNH BÁO MỨC TẢI CAO' : 'TỐT';
                $aiResponse = "> Aegis: [PHÂN TÍCH CPU] Mức độ chiếm dụng vi xử lý hiện đang ở mức " . round($avgCpu, 1) . "%. Trạng thái: {$cpuStatus}. Các tiến trình lõi vận hành ổn định.";
            }
            elseif (str_contains($command, 'ram') || str_contains($command, 'bộ nhớ') || str_contains($command, 'memory')) {
                $ramStatus = $avgRam > 85 ? 'SẮP TRÀN BỘ NHỚ' : 'ỔN ĐỊNH';
                $aiResponse = "> Aegis: [PHÂN TÍCH RAM] Mức tiêu thụ RAM thực tế là " . round($avgRam, 1) . "%. Trạng thái: {$ramStatus}. Tài nguyên bộ nhớ vẫn đủ để đáp ứng các luồng dữ liệu mới.";
            }
            elseif (str_contains($command, 'ổ cứng') || str_contains($command, 'disk') || str_contains($command, 'dung lượng')) {
                $diskStatus = $diskUsedPercent > 90 ? 'CẢNH BÁO ĐẦY Ổ CỨNG' : 'ĐANG TRỐNG NHIỀU';
                $aiResponse = "> Aegis: [PHÂN TÍCH Ổ CỨNG] Dung lượng lưu trữ đang sử dụng {$diskUsedPercent}% (Trống {$diskFreeGB}GB / Tổng {$diskTotalGB}GB). Trạng thái: {$diskStatus}. Dữ liệu log vẫn đang được ghi chép an toàn.";
            }
            elseif (str_contains($command, 'mạng') || str_contains($command, 'network') || str_contains($command, 'băng thông') || str_contains($command, 'tốc độ')) {
                $aiResponse = "> Aegis: [LƯU LƯỢNG MẠNG] Giao diện mạng phản hồi: Connected // Stable. Tốc độ hiện tại - Download: {$netDownload} MB/s | Upload: {$netUpload} MB/s. Không có dấu hiệu nghẽn cổ chai (bottleneck).";
            }
            elseif (str_contains($command, 'tình trạng hệ thống') || str_contains($command, 'status') || str_contains($command, 'tổng quan')) {
                $aiResponse = "> Aegis: [TÌNH TRẠNG HỆ THỐNG] Core Health Score: " . round($healthScore) . "/100. \nCPU: " . round($avgCpu, 1) . "% | RAM: " . round($avgRam, 1) . "% | Disk: {$diskUsedPercent}%. \nTường lửa đã chặn {$threatCount} mối đe dọa. Toàn bộ máy chủ đang trong trạng thái hoàn hảo.";
            }
            elseif (str_contains($command, 'đồ án') || str_contains($command, 'hệ thống này làm gì') || str_contains($command, 'chào') || str_contains($command, 'hello')) {
                $aiResponse = "> Aegis: Xin chào. Tôi là trung tâm trí tuệ nhân tạo của hệ thống Server Health Monitoring. Nhiệm vụ của tôi là giám sát tài nguyên (CPU, RAM, Disk, Network) theo thời gian thực và tự động chặn đứng các cuộc tấn công DDoS.";
            }
            else {
                $aiResponse = "> Aegis: [LỖI NGỮ NGHĨA] Lệnh không xác định. Các khóa quét hỗ trợ: 'tình trạng hệ thống', 'tình trạng ổ cứng', 'kiểm tra mạng', 'thông số ram', 'thông số cpu', 'kiểm tra tấn công'.";
            }
        }

        // =========================================================
        // PHẦN 3: GỬI DỮ LIỆU SANG GIAO DIỆN BLADE
        // =========================================================
        return view('ai_intelligence', [
            'score' => round($healthScore),
            'threats' => $threatCount,
            'insights' => $insights,
            'chartData' => $chartData,
            'aiResponse' => $aiResponse 
        ]);
    }
}