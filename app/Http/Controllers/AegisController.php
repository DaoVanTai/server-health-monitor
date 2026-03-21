<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http; 

class AegisController extends Controller
{
    public function index(Request $request) 
    {
        // =========================================================
        // PHẦN 1: LOGIC TÍNH ĐIỂM SỨC KHỎE (Giữ nguyên của nhóm)
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
            "> [SYSTEM] Aegis Neural Core v4.0 (Gemini 1.5 Flash) active...",
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
        // PHẦN 2: BỘ NÃO AI CHUẨN XÁC, KHÔNG DÙNG DỮ LIỆU ẢO
        // =========================================================
        $chartData = null; 
        $aiResponse = "> Chờ lệnh từ quản trị viên...";

        if ($request->has('ai_command')) {
            $command = $request->get('ai_command');
            
            $historyData = DB::table('server_metrics')
                ->where('created_at', '>=', now()->subDay())
                ->orderBy('created_at', 'asc') 
                ->get();

            $apiKey = env('GEMINI_API_KEY');

            if (empty($apiKey)) {
                $aiResponse = "> Lỗi Hệ Thống: Chưa cấu hình GEMINI_API_KEY trong file .env!";
            } else {
                // Prompt ép AI trả lời chính xác, chuyên nghiệp, kèm data thật
                $prompt = "Bạn là Aegis, một AI quản trị Server bảo mật cao. 
                Người dùng ra lệnh/hỏi: '{$command}'.
                Nhiệm vụ của bạn:
                1. Trả lời câu hỏi một cách thông minh, chính xác, giọng điệu ngầu và chuyên nghiệp. KHÔNG trả lời lan man. (Hiện tại CPU Server đang tải " . round($avgCpu, 1) . "%, RAM " . round($avgRam, 1) . "% - Hãy dùng thông tin này nếu người dùng hỏi về tình trạng máy chủ).
                2. Phân loại lệnh (intent):
                   - Nếu người dùng muốn vẽ biểu đồ CPU, intent = 'draw_cpu'.
                   - Nếu người dùng muốn vẽ biểu đồ RAM, intent = 'draw_ram'.
                   - Nếu hỏi thông thường, intent = 'chat'.
                CHỈ trả về ĐÚNG MỘT khối JSON thuần túy (không markdown ```json):
                {\"intent\": \"tên_intent\", \"reply\": \"câu_trả_lời_của_bạn\"}";

                // Gọi trực tiếp đến Google, dùng đúng model, bỏ Proxy lỗi
                    $googleUrl = "https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key=" . $apiKey;
                try {
                    $response = Http::timeout(15)->withHeaders([
                        'Content-Type' => 'application/json'
                    ])->post($googleUrl, [
                        'contents' => [
                            ['parts' => [['text' => $prompt]]]
                        ]
                    ]);

                    if ($response->successful()) {
                        $resultText = $response->json('candidates.0.content.parts.0.text');
                        
                        if (!empty($resultText)) {
                            // Dọn dẹp JSON rác từ AI nếu có
                            $cleanJson = trim(preg_replace('/```json|```/', '', $resultText));
                            $aiResult = json_decode($cleanJson);

                            if ($aiResult && isset($aiResult->reply)) {
                                $aiResponse = $aiResult->reply;
                                
                                // Nếu AI phân tích đúng lệnh vẽ biểu đồ
                                if ($aiResult->intent === 'draw_cpu') {
                                    $chartData = [
                                        'label' => 'Mức sử dụng CPU (%)',
                                        'labels' => $historyData->pluck('created_at')->map(fn($t) => date('H:i', strtotime($t)))->toArray(),
                                        'values' => $historyData->pluck('cpu_percent')->toArray(),
                                        'color' => '#22d3ee' 
                                    ];
                                } elseif ($aiResult->intent === 'draw_ram') {
                                    $chartData = [
                                        'label' => 'Mức sử dụng RAM (%)',
                                        'labels' => $historyData->pluck('created_at')->map(fn($t) => date('H:i', strtotime($t)))->toArray(),
                                        'values' => $historyData->pluck('ram_percent')->toArray(),
                                        'color' => '#a855f7' 
                                    ];
                                }
                            } else {
                                $aiResponse = "> Aegis: Lỗi phân tích ngữ nghĩa (JSON Parser Error).";
                            }
                        }
                    } else {
                        // In lỗi thật từ Google để dễ fix (Vd: Lỗi Location)
                        $errorMsg = $response->json('error.message') ?? 'Unknown Error';
                        $aiResponse = "> Lỗi kết nối Google API: " . $errorMsg;
                    }
                } catch (\Exception $e) {
                    $aiResponse = "> Lỗi đường truyền mạng: " . $e->getMessage();
                }
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