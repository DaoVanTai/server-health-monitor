<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\SystemLog;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class SystemLogController extends Controller
{
    /**
     * TRUNG TÂM KIỂM SOÁT AN NINH HỢP NHẤT (UNIFIED SECURITY HUB)
     */
    public function index(Request $request)
    {
        // --- PHẦN 1: LẤY LOG TỪ DATABASE (DDoS / Firewall Events) ---
        $query = SystemLog::whereIn('level', ['danger', 'success', 'warning']);

        // Tìm kiếm theo từ khóa
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('message', 'like', "%{$search}%")
                  ->orWhere('source', 'like', "%{$search}%")
                  ->orWhere('ip_address', 'like', "%{$search}%");
            });
        }

        // Lọc theo mức độ
        if ($request->filled('level') && $request->level !== 'all') {
            $query->where('level', $request->level);
        }

        // Lọc khoảng thời gian
        $fromDate = $request->input('from_date', Carbon::today()->format('Y-m-d'));
        $toDate = $request->input('to_date', Carbon::today()->format('Y-m-d'));

        if ($fromDate && $toDate) {
            $query->whereBetween('created_at', [$fromDate . ' 00:00:00', $toDate . ' 23:59:59']);
        }

        $dbLogs = $query->orderBy('created_at', 'desc')->get();

        // --- PHẦN 2: QUÉT LOG TỪ HỆ ĐIỀU HÀNH (SSH Tracker Logic) ---
        $sshData = $this->parseSshLogs();

        // --- PHẦN 3: THỐNG KÊ BIỂU ĐỒ (7 Ngày) ---
        $chartData = $this->getChartData();

        return view('logs', compact(
            'dbLogs', 
            'sshData', 
            'chartData',
            'fromDate', 
            'toDate'
        ));
    }

    /**
     * Logic bóc tách file auth.log của Linux (Kế thừa từ SecurityController)
     */
    private function parseSshLogs()
    {
        if (PHP_OS_FAMILY === 'Linux') {
            // Lấy 500 dòng cuối để đảm bảo tốc độ tải trang
            $logContent = shell_exec('sudo cat /var/log/auth.log | grep sshd | tail -n 500 2>/dev/null');
        } else {
            $logContent = null; 
        }

        // Dữ liệu giả lập nếu không chạy trên Linux (để demo)
        if (!$logContent) {
            $logContent = "Mar 25 10:01:22 server sshd: Failed password for root from 1.2.3.4 port 22 ssh2\n"
                        . "Mar 25 10:10:00 server sshd: Accepted password for admin from 103.27.61.76 port 22 ssh2";
        }

        $failedAttempts = [];
        $successfulLogins = [];
        $targetedUsers = [];
        $lines = explode("\n", trim($logContent));

        foreach ($lines as $line) {
            if (empty(trim($line))) continue;

            preg_match('/^([a-zA-Z]{3}\s+\d+\s\d{2}:\d{2}:\d{2})/', $line, $timeMatches);
            $time = $timeMatches[1] ?? 'Unknown';

            // 1. Bắt Brute-force (Thử sai)
            if (preg_match('/Failed (?:password|publickey) for (?:invalid user )?([^\s]+) from ([0-9\.]+)/', $line, $matches)) {
                $user = $matches[1]; $ip = $matches[2];
                $failedAttempts[$ip]['count'] = ($failedAttempts[$ip]['count'] ?? 0) + 1;
                if (!isset($failedAttempts[$ip]['users'])) $failedAttempts[$ip]['users'] = [];
                if (!in_array($user, $failedAttempts[$ip]['users'])) $failedAttempts[$ip]['users'][] = $user;
                $targetedUsers[$user] = ($targetedUsers[$user] ?? 0) + 1;
            }

            // 2. Bắt Đăng nhập thành công
            if (preg_match('/Accepted (?:password|publickey) for ([^\s]+) from ([0-9\.]+)/', $line, $matches)) {
                $successfulLogins[] = ['time' => $time, 'user' => $matches[1], 'ip' => $matches[2]];
            }
        }

        // Sắp xếp
        arsort($targetedUsers);
        uasort($failedAttempts, fn($a, $b) => $b['count'] <=> $a['count']);

        return [
            'topUsers' => array_slice($targetedUsers, 0, 5, true),
            'topAttackers' => array_slice($failedAttempts, 0, 5, true),
            'recentLogins' => array_slice(array_reverse($successfulLogins), 0, 10)
        ];
    }

    private function getChartData()
    {
        $labels = []; $blocked = []; $alerts = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i)->format('Y-m-d');
            $labels[] = Carbon::now()->subDays($i)->format('d/m');
            $blocked[] = SystemLog::whereDate('created_at', $date)->where('level', 'danger')->count();
            $alerts[] = SystemLog::whereDate('created_at', $date)->where('level', 'warning')->count();
        }
        return ['labels' => $labels, 'blocked' => $blocked, 'alerts' => $alerts];
    }
}