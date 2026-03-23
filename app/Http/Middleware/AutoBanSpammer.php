<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use App\Models\SystemLog; 

class AutoBanSpammer
{
    public function handle(Request $request, Closure $next)
    {
        $ip = $request->ip();

        // ==========================================
        // 0. DANH SÁCH TRẮNG TỐI THƯỢNG (WHITELIST)
        // ==========================================
        // Đặt ở trên cùng: Những IP này là BẤT KHẢ XÂM PHẠM, đi thẳng không cần hỏi giấy tờ.
        $safeIps = [
            '127.0.0.1',
            '::1',
            '103.27.61.76', // IP máy chủ VPS của bạn
            
            // 💡 MẸO CHO NHÓM BẠN:
            // Hãy lên Google gõ "What is my IP", lấy IP Wifi nhà bạn/trường bạn dán vào đây
            // Ví dụ: '14.232.123.45',
        ];

        // Nếu là IP quen thuộc hoặc mạng LAN nội bộ -> Trải thảm đỏ mời vào luôn
        if (in_array($ip, $safeIps) || str_starts_with($ip, '192.168.') || str_starts_with($ip, '10.')) {
            return $next($request); 
        }

        // ==========================================
        // 1. KIỂM TRA SỔ ĐEN (BLACKLIST)
        // ==========================================
        $isBlocked = DB::table('blacklists')->where('ip_address', $ip)->exists();
        if ($isBlocked) {
            abort(403, 'AEGIS FIREWALL: IP CỦA BẠN ĐÃ BỊ CẤM TRUY CẬP DO HÀNH VI ĐÁNG NGỜ.');
        }

        // ==========================================
        // 2. KIM BÀI MIỄN TỬ (DÀNH CHO NGƯỜI DÙNG BÌNH THƯỜNG)
        // ==========================================
        if (Auth::check()) {
            return $next($request);
        }

        if ($request->is('login', 'logout', '2fa*')) {
            return $next($request);
        }

        // ĐIỂM SỬA QUAN TRỌNG: Bỏ qua các file tĩnh và các luồng API gọi ngầm của Dashboard
        if ($request->is(
            '*.css', '*.js', '*.png', '*.jpg', '*.jpeg', '*.svg', '*.ico', '*.woff2', '*.ttf',
            'api/*', 'livewire/*', '_debugbar/*' // Bỏ qua dữ liệu ngầm để không đếm nhầm là DDoS
        )) {
            return $next($request);
        }

        // ==========================================
        // 3. THUẬT TOÁN BẮT SPAMMER / DDoS
        // ==========================================
        $cacheKey = 'aegis_flood_count_' . $ip;

        if (!Cache::has($cacheKey)) {
            Cache::put($cacheKey, 1, 60); 
            $requests = 1;
        } else {
            $requests = Cache::increment($cacheKey);
        }

        // Nâng ngưỡng báo động lên 500 để trừ hao cho các thao tác F5 chính đáng
        if ($requests > 500) {
            
            DB::table('blacklists')->insertOrIgnore([
                'ip_address' => $ip,
                'reason' => "Auto-ban by Aegis: Phát hiện lưu lượng Flood/DDoS ($requests req/min)",
                'created_at' => now(),
                'updated_at' => now()
            ]);

            SystemLog::create([
                'level' => 'danger', // Chuyển thành Đỏ để lọt vào Bảng Xếp Hạng Top IP
                'source' => 'Aegis Auto-Ban (DDoS Protection)',
                'message' => "Phát hiện lưu lượng Ping/DDoS bất thường ($requests requests/phút). Đã tự động kích hoạt lá chắn bảo vệ.",
                'ip_address' => $ip,
            ]);

            Log::warning("Aegis Firewall đã tự động BAN IP: $ip do spam $requests req/min");
            Cache::forget($cacheKey);

            // ==========================================================
            // 4. PHONG ẤN TẬN GỐC TRÊN LINUX FIREWALL (UFW)
            // ==========================================================
            if (PHP_OS_FAMILY === 'Linux') {
                try {
                    shell_exec("sudo ufw deny from " . escapeshellarg($ip));
                } catch (\Exception $e) {
                    Log::error("Aegis OS-Ban Error: " . $e->getMessage());
                }
            }

            abort(403, 'AEGIS: PHÁT HIỆN TẤN CÔNG DDoS. KẾT NỐI BỊ TỪ CHỐI NGAY LẬP TỨC!');
        }

        return $next($request);
    }
}