<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth; // 1. BẮT BUỘC NẠP THÊM THƯ VIỆN Auth

class AutoBanSpammer
{
    public function handle(Request $request, Closure $next)
    {
        // 2. [TÍNH NĂNG MỚI]: KIM BÀI MIỄN TỬ (WHITELIST)
        // Nếu người dùng đã đăng nhập hợp lệ vào hệ thống -> Cho qua ngay lập tức, bỏ qua mọi rào cản
        if (Auth::check()) {
            return $next($request);
        }

        $ip = $request->ip();

        // 1. Kiểm tra xem IP này đã bị chặn trong Firewall chưa
        $isBlocked = DB::table('blacklists')->where('ip_address', $ip)->exists();
        if ($isBlocked) {
            abort(403, 'Aegis Firewall: IP của bạn đã bị cấm truy cập do hành vi đáng ngờ.');
        }

        // 2. Đếm số lượng request của IP này trong 1 phút (Chống Spam cho khách vãng lai)
        // Nếu là IP nội bộ (localhost) thì bỏ qua
        if ($ip !== '127.0.0.1' && !str_starts_with($ip, '192.168.')) {
            $cacheKey = 'spam_count_' . $ip;
            $requests = Cache::get($cacheKey, 0);

            // Giới hạn: 50 luồng request / 1 phút
            if ($requests > 50) {
                // TỰ ĐỘNG THÊM VÀO FIREWALL DATABASE (Giao diện Web)
                DB::table('blacklists')->insert([
                    'ip_address' => $ip,
                    'reason' => 'Auto-ban by Aegis: Phát hiện tấn công Spam/DDoS',
                    'created_at' => now(),
                    'updated_at' => now()
                ]);

                // [TÍNH NĂNG MỚI] PHONG ẤN TẬN GỐC TRÊN HỆ ĐIỀU HÀNH TỰ ĐỘNG (OS-LEVEL)
                if (PHP_OS_FAMILY === 'Linux') {
                    try {
                        // Khóa toàn bộ các cổng (22, 80, 443, 3306...) từ IP này
                        $command = "sudo ufw deny from " . escapeshellarg($ip);
                        shell_exec($command);
                    } catch (\Exception $e) {
                        // Nếu chưa có quyền sudo, ghi lỗi vào file log thay vì làm chết web
                        Log::error("Không thể thực thi lệnh Firewall OS (Auto-ban): " . $e->getMessage());
                    }
                }

                // Xóa biến đếm
                Cache::forget($cacheKey);

                // Đổi thông báo để ngầu hơn
                abort(403, 'Aegis Firewall: Đã phát hiện tấn công DDoS. IP của bạn đã bị khóa trên TOÀN BỘ các cổng hệ thống!');
            }

            // Tăng biến đếm lên 1, thời gian sống 60 giây
            Cache::put($cacheKey, $requests + 1, 60);
        }

        return $next($request);
    }
}