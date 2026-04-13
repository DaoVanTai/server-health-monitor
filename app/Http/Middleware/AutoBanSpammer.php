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

        // 1. WHITELIST (IP Tin cậy - Không bao giờ bị chặn)
        $safeIps = ['127.0.0.1', '::1', '103.27.61.76', '116.106.96.28']; 
        if (in_array($ip, $safeIps)) return $next($request);

        // 2. KIM BÀI MIỄN TỬ (Nếu đã Đăng nhập + Qua 2FA -> Cho qua luôn)
        // Đây là phần giúp bạn không bao giờ bị ban khi đang làm việc
        if (Auth::check()) {
            return $next($request);
        }

        // 3. MIỄN TRỪ TRANG ĐĂNG NHẬP (Để user còn có chỗ mà login)
        if ($request->is('login', 'logout', '2fa*', 'verify-2fa*', 'css/*', 'js/*', 'images/*')) {
            return $next($request);
        }

        // 4. KIỂM TRA BLACKLIST (Hiện diện trên trang Security/Firewall)
        $isBlocked = DB::table('blacklists')->where('ip_address', $ip)->exists();
        if ($isBlocked) {
            abort(403, 'AEGIS SHIELD: IP của bạn đã bị phong tỏa vĩnh viễn.');
        }

        // 5. THUẬT TOÁN "5 NHÁT BAN LUÔN"
        $cacheKey = 'flood_' . $ip;
        $requests = Cache::get($cacheKey, 0) + 1;
        Cache::put($cacheKey, $requests, 60); // Lưu trong 60 giây

        // NGƯỠNG THIẾT LẬP: 5 LẦN
        if ($requests > 5) {
            
            // A. GHI VÀO BẢNG BLACKLIST (Để hiện bên trang Security/Firewall)
            DB::table('blacklists')->insertOrIgnore([
                'ip_address' => $ip,
                'reason' => "Spam Attack: $requests requests/min (Threshold: 5)",
                'created_at' => now(),
                'updated_at' => now()
            ]);

            // B. GHI VÀO BẢNG SYSTEM_LOGS (Để hiện bên trang Logs/Audit)
            SystemLog::create([
                'level' => 'danger',
                'source' => 'Aegis Auto-Shield',
                'message' => "Phát hiện tấn công dò quét (Flood). IP $ip đã thực hiện $requests yêu cầu liên tiếp. Kích hoạt lệnh trừng phạt.",
                'ip_address' => $ip,
            ]);

            // C. CHẶN TẬN GỐC TRÊN OS (UFW)
            if (PHP_OS_FAMILY === 'Linux') {
                shell_exec("sudo ufw deny from " . escapeshellarg($ip));
            }

            Log::alert("Hệ thống AEGIS đã BAN IP: $ip (Spam detected)");
            Cache::forget($cacheKey);

            abort(403, 'AEGIS: PHÁT HIỆN HÀNH VI SPAM. IP ĐÃ BỊ CHẶN TỨC THÌ!');
        }

        return $next($request);
    }
}