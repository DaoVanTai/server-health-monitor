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
        // 1. KIỂM TRA SỔ ĐEN (BLACKLIST) ĐẦU TIÊN
        // ==========================================
        $isBlocked = DB::table('blacklists')->where('ip_address', $ip)->exists();
        if ($isBlocked) {
            abort(403, 'AEGIS FIREWALL: IP CỦA BẠN ĐÃ BỊ CẤM TRUY CẬP DO HÀNH VI ĐÁNG NGỜ.');
        }

        // ==========================================
        // 2. KIM BÀI MIỄN TỬ (BẢO VỆ NGƯỜI DÙNG THẬT)
        // ==========================================
        if (Auth::check()) {
            return $next($request);
        }

        if ($request->is('login', 'logout', '2fa*')) {
            return $next($request);
        }

        if ($request->is('*.css', '*.js', '*.png', '*.jpg', '*.jpeg', '*.svg', '*.ico', '*.woff2', '*.ttf')) {
            return $next($request);
        }

        // ==========================================
        // 3. THUẬT TOÁN BẮT SPAMMER / DDoS
        // ==========================================
        
        $safeIps = ['127.0.0.1', '::1'];
        if (!in_array($ip, $safeIps) && !str_starts_with($ip, '192.168.') && !str_starts_with($ip, '10.')) {
            
            $cacheKey = 'aegis_flood_count_' . $ip;

            if (!Cache::has($cacheKey)) {
                Cache::put($cacheKey, 1, 60); 
                $requests = 1;
            } else {
                $requests = Cache::increment($cacheKey);
            }

            if ($requests > 300) {
                // Đưa IP này vào Sổ đen Database
                DB::table('blacklists')->insertOrIgnore([
                    'ip_address' => $ip,
                    'reason' => "Auto-ban by Aegis: Phát hiện lưu lượng Flood/DDoS ($requests req/min)",
                    'created_at' => now(),
                    'updated_at' => now()
                ]);

                // GHI VÀO LOG HỆ THỐNG (REAL LOG)
                SystemLog::create([
                    'level' => 'warning',
                    'source' => 'Aegis Auto-Ban (DDoS Protection)',
                    'message' => "Phát hiện lưu lượng Ping/DDoS bất thường ($requests requests/phút). Đã tự động kích hoạt lá chắn bảo vệ.",
                    'ip_address' => $ip,
                ]);

                Log::warning("Aegis Firewall đã tự động BAN IP: $ip do spam $requests req/min");
                Cache::forget($cacheKey);

                // ==========================================================
                // 4. PHONG ẤN TẬN GỐC TRÊN LINUX FIREWALL (UFW)
                // Khóa đứng mọi luồng giao tiếp vào tất cả các cổng (6060, 22, 80...)
                // ==========================================================
                if (PHP_OS_FAMILY === 'Linux') {
                    try {
                        // Dùng escapeshellarg để bảo mật biến $ip khi truyền vào Shell
                        shell_exec("sudo ufw deny from " . escapeshellarg($ip));
                    } catch (\Exception $e) {
                        Log::error("Aegis OS-Ban Error: " . $e->getMessage());
                    }
                }

                // Đá văng ngay lập tức khỏi web
                abort(403, 'AEGIS: PHÁT HIỆN TẤN CÔNG DDoS. KẾT NỐI BỊ TỪ CHỐI NGAY LẬP TỨC!');
            }
        }

        return $next($request);
    }
}