<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class AutoBanSpammer
{
    public function handle(Request $request, Closure $next)
    {
        $ip = $request->ip();

        // ==========================================
        // 1. KIỂM TRA SỔ ĐEN (BLACKLIST) ĐẦU TIÊN
        // Nếu IP đã có tiền án, đuổi cổ ngay lập tức, không cho chạy lệnh gì thêm
        // ==========================================
        $isBlocked = DB::table('blacklists')->where('ip_address', $ip)->exists();
        if ($isBlocked) {
            abort(403, 'AEGIS FIREWALL: IP CỦA BẠN ĐÃ BỊ CẤM TRUY CẬP DO HÀNH VI ĐÁNG NGỜ.');
        }

        // ==========================================
        // 2. KIM BÀI MIỄN TỬ (BẢO VỆ NGƯỜI DÙNG THẬT)
        // ==========================================
        // Ưu tiên 1: Đã đăng nhập thành công (Admin) -> Cho qua mọi rào cản
        if (Auth::check()) {
            return $next($request);
        }

        // Ưu tiên 2: Đang đứng ở trang Đăng nhập / 2FA -> Cho qua để họ gõ mật khẩu thoải mái
        if ($request->is('login', 'logout', '2fa*')) {
            return $next($request);
        }

        // Ưu tiên 3: Loại trừ các file giao diện (CSS, JS, Hình ảnh, Font chữ)
        // Vì 1 trang web load có thể gọi hàng chục file tĩnh cùng lúc, đây không phải là spam
        if ($request->is('*.css', '*.js', '*.png', '*.jpg', '*.jpeg', '*.svg', '*.ico', '*.woff2', '*.ttf')) {
            return $next($request);
        }

        // ==========================================
        // 3. THUẬT TOÁN BẮT SPAMMER / DDoS
        // Chỉ đếm những luồng truy cập vào các đường dẫn nhạy cảm (API, Trang chủ...)
        // ==========================================
        
        // Bỏ qua các IP nội bộ an toàn (như localhost của Server)
        $safeIps = ['127.0.0.1', '::1'];
        if (!in_array($ip, $safeIps) && !str_starts_with($ip, '192.168.') && !str_starts_with($ip, '10.')) {
            
            $cacheKey = 'aegis_flood_count_' . $ip;

            // Logic đếm CHUẨN: Nếu chưa có thì tạo mới sống 60 giây. Nếu có rồi thì cộng thêm 1.
            if (!Cache::has($cacheKey)) {
                Cache::put($cacheKey, 1, 60); 
                $requests = 1;
            } else {
                $requests = Cache::increment($cacheKey);
            }

            // NGƯỠNG BÁO ĐỘNG: 300 Lần / 60 Giây
            // Người thật click chuột nhanh nhất cũng chỉ 50-60 lần/phút. 
            // Vượt qua 300 chắc chắn là dùng Tool Ping/DDoS.
            if ($requests > 300) {
                // Đưa IP này vào Sổ đen Database
                DB::table('blacklists')->insertOrIgnore([
                    'ip_address' => $ip,
                    'reason' => "Auto-ban by Aegis: Phát hiện lưu lượng Flood/DDoS ($requests req/min)",
                    'created_at' => now(),
                    'updated_at' => now()
                ]);

                // Ghi vào Log để Admin kiểm tra sau
                Log::warning("Aegis Firewall đã tự động BAN IP: $ip do spam $requests req/min");

                // Xóa bộ đếm để giải phóng RAM cho Server
                Cache::forget($cacheKey);

                // Lưu ý: Mình đã gỡ bỏ lệnh 'sudo ufw' vì tự động chạy lệnh này rất dễ 
                // khiến bạn tự khóa mình khỏi SSH. Chặn bằng Database (app-level) là đủ an toàn rồi.
            }
        }

        return $next($request);
    }
}