<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
// use Illuminate\Support\Facades\Auth; // Đã bỏ vì không dùng được ở tầng Global
use App\Models\SystemLog; 
use App\Services\TelegramService;

class AutoBanSpammer
{
    public function handle(Request $request, Closure $next)
    {
        $ip = $request->ip();

        // ==========================================
        // 1. KIM BÀI MIỄN TỬ (ĐỌC TỪ CACHE)
        // ==========================================
        // Lấy danh sách IP Admin đã đăng nhập thành công từ Cache
        $adminIps = Cache::get('admin_safe_ips', []);
        
        // Gộp với danh sách IP an toàn cố định (Localhost, Server IP...)
        $safeIps = array_merge($adminIps, ['127.0.0.1', '::1', '103.27.61.76', '116.106.96.28']); 

        // Nếu IP nằm trong danh sách V.I.P -> Cho qua ngay lập tức!
        if (in_array($ip, $safeIps)) {
            return $next($request);
        }

        // ==========================================
        // 2. MIỄN TRỪ TRANG ĐĂNG NHẬP / TÀI NGUYÊN TĨNH
        // ==========================================
        // KHÔNG miễn trừ trang /login (vì hacker có thể flood trang login)
        // Chỉ miễn trừ các file tĩnh để web không bị lỗi giao diện
        if ($request->is('css/*', 'js/*', 'images/*', 'assets/*')) {
            return $next($request);
        }

        // ==========================================
        // 3. KIỂM TRA BLACKLIST DB
        // ==========================================
        $isBlocked = DB::table('blacklists')->where('ip_address', $ip)->exists();
        if ($isBlocked) {
            abort(403, 'AEGIS SHIELD: IP của bạn đã bị phong tỏa vĩnh viễn.');
        }

        // ==========================================
        // 4. THUẬT TOÁN "5 NHÁT BAN LUÔN" (CHỐNG FLOOD)
        // ==========================================
        $cacheKey = 'flood_' . $ip;
        $requests = Cache::get($cacheKey, 0) + 1;
        Cache::put($cacheKey, $requests, 60); // Lưu đếm nhịp trong 60 giây

        if ($requests > 5) {
            // A. CHẶN TỨC THÌ Ở TẦNG OS (UFW)
            if (PHP_OS_FAMILY === 'Linux') {
                shell_exec("sudo /usr/sbin/ufw insert 1 deny from " . escapeshellarg($ip));
            }

            // B. GHI VÀO DB (Blacklist & Logs)
            DB::table('blacklists')->insertOrIgnore([
                'ip_address' => $ip,
                'reason' => "Spammer: Flood Attack detected ($requests r/m)",
                'created_at' => now(), 'updated_at' => now()
            ]);
            
            SystemLog::create([
                'level' => 'danger',
                'source' => 'Aegis Auto-Shield',
                'message' => "Hệ thống tự động chặn Spammer: $ip. Tần suất: $requests req/min.",
                'ip_address' => $ip,
            ]);

            // C. THÔNG BÁO TELEGRAM
            $msg = "🚨 <b>AEGIS SHIELD: PHONG TỎA KHẨN CẤP</b>\n";
            $msg .= "IP <code>$ip</code> bị chặn do tấn công Flood.";
            TelegramService::sendMessage($msg);

            // D. XÓA ĐẾM NHỊP VÀ CHẶN HIỂN THỊ
            Cache::forget($cacheKey);
            abort(403, 'AEGIS: PHÁT HIỆN HÀNH VI SPAM. IP ĐÃ BỊ CHẶN TỨC THÌ!');
        }

        return $next($request);
    }
}