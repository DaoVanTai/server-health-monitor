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
        // 1. KIM BÀI MIỄN TỬ (WHITELIST)
        // Nếu đã đăng nhập thành công -> Admin hợp lệ, bỏ qua mọi rào cản
        if (Auth::check()) {
            return $next($request);
        }

        $ip = $request->ip();

        // 2. KIỂM TRA DANH SÁCH ĐEN (BLACKLIST)
        $isBlocked = DB::table('blacklists')->where('ip_address', $ip)->exists();
        if ($isBlocked) {
            abort(403, 'AEGIS FIREWALL: IP CỦA BẠN ĐÃ BỊ CẤM TRUY CẬP DO HÀNH VI ĐÁNG NGỜ.');
        }

        // 3. BỎ QUA TÀI NGUYÊN TĨNH (TRÁNH BAN OAN)
        // Khi load web, trình duyệt sẽ tự động tải css, js, ảnh. Đây không phải là hành vi spam.
        if ($request->is('*.css', '*.js', '*.png', '*.jpg', '*.svg', '*.ico', '*.woff2')) {
            return $next($request);
        }

        // 4. THUẬT TOÁN ĐẾM REQUEST (CHỐNG DDoS / BRUTE-FORCE)
        // Bỏ qua các IP nội bộ an toàn
        if ($ip !== '127.0.0.1' && !str_starts_with($ip, '192.168.') && !str_starts_with($ip, '10.')) {
            $cacheKey = 'spam_count_' . $ip;

            // Sửa logic đếm: Khởi tạo bộ đếm 60 giây cho lần đầu tiên
            Cache::add($cacheKey, 0, 60);
            
            // Tăng biến đếm lên 1
            $requests = Cache::increment($cacheKey);

            // Nâng ngưỡng lên 150 request / phút. 
            // Người thật không thể thao tác 150 lần/phút, chỉ có Tool/Bot mới làm được.
            if ($requests > 150) {
                // Thêm vào DB (dùng insertOrIgnore để tránh lỗi trùng lặp khi bị DDoS quá nhanh)
                DB::table('blacklists')->insertOrIgnore([
                    'ip_address' => $ip,
                    'reason' => 'Auto-ban by Aegis: Phát hiện lưu lượng DDoS (>150 req/min)',
                    'created_at' => now(),
                    'updated_at' => now()
                ]);

                // PHONG ẤN TẬN GỐC TRÊN HỆ ĐIỀU HÀNH TỰ ĐỘNG (OS-LEVEL)
                if (PHP_OS_FAMILY === 'Linux') {
                    try {
                        // Khóa toàn bộ các cổng từ IP này
                        $command = "sudo ufw deny from " . escapeshellarg($ip);
                        shell_exec($command);
                    } catch (\Exception $e) {
                        Log::error("Không thể thực thi lệnh Firewall OS (Auto-ban): " . $e->getMessage());
                    }
                }

                // Xóa biến đếm để giải phóng bộ nhớ
                Cache::forget($cacheKey);

                //abort(403, 'Aegis Firewall: Đã phát hiện tấn công DDoS. IP của bạn đã bị khóa trên TOÀN BỘ các cổng hệ thống!');
            }
        }

        return $next($request);
    }
}