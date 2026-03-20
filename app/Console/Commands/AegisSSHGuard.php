<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AegisSSHGuard extends Command
{
    // Tên lệnh để chạy trong Terminal
    protected $signature = 'aegis:ssh-guard';
    protected $description = 'Quét nhật ký hệ thống Linux để phát hiện tấn công Brute-force cổng 22';

    public function handle()
    {
        // Đường dẫn file log lưu lịch sử đăng nhập cổng 22 của Ubuntu/Debian
        $logFile = '/var/log/auth.log'; 

        if (!file_exists($logFile)) {
            $this->error("Không tìm thấy file log hệ thống (Tính năng này chỉ chạy trên Linux/Ubuntu).");
            return;
        }

        // Đọc 1000 dòng cuối cùng của file log để tìm kiếm
        $logs = shell_exec("tail -n 1000 " . escapeshellarg($logFile));
        
        // Dùng Regex (Biểu thức chính quy) để vớt các dòng chứa "Failed password" và lấy địa chỉ IP
        preg_match_all('/Failed password for (invalid user )?.*? from ([0-9\.]+) port/i', $logs, $matches);

        if (!empty($matches[2])) {
            // Đếm số lần nhập sai của từng IP
            $ipCounts = array_count_values($matches[2]);

            foreach ($ipCounts as $ip => $failedAttempts) {
                // Nếu 1 IP nhập sai mật khẩu cổng 22 quá 5 lần -> Kẻ tấn công Brute-force
                if ($failedAttempts >= 5) {
                    
                    // Kiểm tra xem đã khóa IP này chưa để tránh khóa trùng
                    $isBlocked = DB::table('blacklists')->where('ip_address', $ip)->exists();
                    
                    if (!$isBlocked) {
                        // 1. Ghi vào bảng Security Firewall trên Web
                        DB::table('blacklists')->insert([
                            'ip_address' => $ip,
                            'reason' => 'Aegis SSH-Guard: Phát hiện tấn công Brute-force cổng 22 (' . $failedAttempts . ' lần thử)',
                            'created_at' => now(),
                            'updated_at' => now()
                        ]);

                        // 2. Gọi lệnh Hệ điều hành phong ấn IP đó ngay lập tức
                        if (PHP_OS_FAMILY === 'Linux') {
                            shell_exec("sudo ufw deny from " . escapeshellarg($ip));
                        }

                        $this->info("🚨 Đã phong ấn IP tấn công cổng 22: " . $ip);
                    }
                }
            }
        }
        $this->info("Hoàn tất tuần tra bảo mật SSH.");
    }
}