<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http; // <-- Thêm thư viện để gọi API ra ngoài

class AegisController extends Controller
{
    public function index(Request $request) 
    {
        // =========================================================
        // PHẦN 1: LOGIC TÍNH ĐIỂM SỨC KHỎE (GIỮ NGUYÊN)
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

        // Đổi version thành 4.0 NLP Enabled cho ngầu
        $insights = [
            "> [SYSTEM] Aegis Neural Core v4.0 (NLP Enabled) initialized...",
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
        // PHẦN 2: TƯƠNG TÁC AI BẰNG NGÔN NGỮ TỰ NHIÊN (GEMINI API)
        // =========================================================
        
        $chartData = null; 
        $aiResponse = "> Chờ lệnh từ quản trị viên...";

        if ($request->has('ai_command')) {
            $command = $request->get('ai_command');
            $aiResponse = "> AEGIS: Đang phân tích ngữ nghĩa: '$command'...";

            // Lấy dữ liệu 24h qua sẵn sàng để vẽ nếu AI ra lệnh
            $historyData = DB::table('server_metrics')
                ->where('created_at', '>=', now()->subDay())
                ->orderBy('created_at', 'asc') 
                ->get();

            // Lấy API Key từ cấu hình
            $apiKey = env('GEMINI_API_KEY');

            if (empty($apiKey)) {
                $aiResponse = "> Aegis Lỗi: Chưa cấu hình GEMINI_API_KEY trong file .env!";
            } else {
                // Prompt: "Dạy" AI cách đọc hiểu và trả lời dưới dạng JSON
                $prompt = "Bạn là Aegis, một AI quản trị Server chuyên nghiệp. 
                Người dùng ra lệnh: '$command'. 
                Nhiệm vụ của bạn: Phân tích ý định của lệnh này. 
                - Nếu người dùng muốn xem/vẽ biểu đồ liên quan đến CPU, intent là 'draw_cpu'. 
                - Nếu người dùng muốn xem/vẽ biểu đồ liên quan đến RAM, intent là 'draw_ram'. 
                - Nếu hỏi chuyện khác hoặc chào hỏi bình thường, intent là 'chat'.
                Hãy tạo ra một câu trả lời ngắn gọn, ngầu và mang phong cách hacker/chuyên gia bảo mật.
                TRẢ VỀ ĐÚNG MỘT CHUỖI JSON thuần túy (không chứa ký tự markdown như ```json, không xuống dòng thừa) với cấu trúc: 
                {\"intent\": \"tên_intent_ở_đây\", \"reply\": \"câu_trả_lời_của_bạn_ở_đây\"}";

                try {
                    // Gắn thêm Header để Google hiểu định dạng
                    $response = Http::withHeaders([
                        'Content-Type' => 'application/json'
                    ])->post("https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key=" . $apiKey, [
                        'contents' => [
                            ['parts' => [['text' => $prompt]]]
                        ]
                    ]);

                    // NẾU GOOGLE BÁO LỖI (Ví dụ: Sai API, Hết hạn mức...)
                    if ($response->failed()) {
                        $aiResponse = "> Aegis Offline: Google từ chối kết nối. Lỗi chi tiết: " . $response->body();
                    } 
                    // NẾU THÀNH CÔNG
                    else {
                        $resultText = $response->json('candidates.0.content.parts.0.text');
                        
                        // Kiểm tra nếu Google không trả về chữ
                        if (empty($resultText)) {
                            $aiResponse = "> Aegis: Dữ liệu trả về bị trống. Toàn bộ phản hồi: " . $response->body();
                        } else {
                            // Dọn dẹp JSON
                            $cleanJson = str_replace(['```json', '```', "\n", "\r"], '', $resultText);
                            $aiResult = json_decode(trim($cleanJson));

                            if ($aiResult && isset($aiResult->intent)) {
                                $aiResponse = "> Aegis: " . $aiResult->reply;
                                
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
                                $aiResponse = "> Aegis Lỗi Format: AI không trả về đúng chuẩn JSON. Phản hồi của AI: " . $resultText;
                            }
                        }
                    }

                } catch (\Exception $e) {
                    $aiResponse = "> Aegis Offline: Lỗi mạng nội bộ. Chi tiết: " . $e->getMessage();
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