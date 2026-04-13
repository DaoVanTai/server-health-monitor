<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use App\Models\SystemLog; 
use App\Services\TelegramService;

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

        if ($requests > 5) {
    // 1. CHẶN TỨC THÌ Ở TẦNG OS (UFW) - Lệnh này phải chạy đầu tiên
    if (PHP_OS_FAMILY === 'Linux') {
        // Sử dụng "insert 1" để đưa luật chặn lên đầu hàng đợi của Firewall
        shell_exec("sudo /usr/sbin/ufw insert 1 deny from " . escapeshellarg($ip));
    }

    // 2. GHI VÀO DB (Blacklist & Logs) - Thực hiện sau khi đã ngắt kết nối IP đó
    DB::table('blacklists')->insertOrIgnore([
        'ip_address' => $ip,
        'reason' => "Spammer: Flood Attack detected ($requests r/m)",
        'created_at' => now(), 'updated_at' => now()
    ]);

    // 3. THÔNG BÁO TELEGRAM
    $msg = "🚨 <b>AEGIS SHIELD: PHONG TỎA KHẨN CẤP</b>\nIP <code>$ip</code> bị chặn do tấn công Flood.";
    TelegramService::sendMessage($msg);

    Cache::forget($cacheKey);
    abort(403);
}

        return $next($request);
    }
}