<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\SystemLog;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class SystemLogController extends Controller
{
    public function index(Request $request)
    {
        $query = SystemLog::query();

        // 5.1 & 5.2 CHỨC NĂNG TÌM KIẾM & LỌC THEO LOẠI
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('message', 'like', "%{$search}%")
                  ->orWhere('source', 'like', "%{$search}%")
                  ->orWhere('ip_address', 'like', "%{$search}%");
            });
        }

        if ($request->filled('level') && $request->level !== 'all') {
            $query->where('level', $request->level);
        }

        // 5.3 LỌC THEO THỜI GIAN (whereBetween)
        if ($request->filled('from_date') && $request->filled('to_date')) {
            $from = $request->from_date . ' 00:00:00';
            $to = $request->to_date . ' 23:59:59';
            $query->whereBetween('created_at', [$from, $to]);
        }

        // 6.1 & 6.2 THỐNG KÊ ANALYTICS CẤP CAO
        $stats = [
            'total' => SystemLog::count(),
            'attack' => SystemLog::where('level', 'danger')->count(), // Tấn công (Đỏ)
            'alert' => SystemLog::where('level', 'warning')->count(), // Cảnh báo (Vàng)
            'system' => SystemLog::where('level', 'info')->count(),   // Hệ thống (Xanh)
        ];

        // Lấy Top 5 IP tấn công nhiều nhất
        $topIps = SystemLog::select('ip_address', DB::raw('count(*) as total'))
            ->whereNotNull('ip_address')
            ->where('level', 'danger')
            ->groupBy('ip_address')
            ->orderByDesc('total')
            ->limit(5)
            ->get();

        // 6.3 CHUẨN BỊ DỮ LIỆU CHO BIỂU ĐỒ CHART.JS (7 ngày gần nhất)
        $chartLabels = [];
        $chartAttack = [];
        $chartAlert = [];

        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i)->format('Y-m-d');
            $chartLabels[] = Carbon::now()->subDays($i)->format('d/m');
            
            $chartAttack[] = SystemLog::whereDate('created_at', $date)->where('level', 'danger')->count();
            $chartAlert[] = SystemLog::whereDate('created_at', $date)->where('level', 'warning')->count();
        }

        // Lấy danh sách hiển thị
        $logs = $query->orderBy('created_at', 'desc')->paginate(15)->appends($request->query());

        return view('logs', compact('logs', 'stats', 'topIps', 'chartLabels', 'chartAttack', 'chartAlert'));
    }

    // 3.2 ĐỌC LOG BẢO MẬT (SSH ATTACK) TỪ LINUX
    public function syncSshLogs()
    {
        if (PHP_OS_FAMILY === 'Linux') {
            try {
                // Đọc 20 dòng log SSH thất bại mới nhất
                $logLines = shell_exec("grep 'Failed password' /var/log/auth.log | tail -n 20");
                
                if ($logLines) {
                    $lines = explode("\n", trim($logLines));
                    $count = 0;

                    foreach ($lines as $line) {
                        // Tách IP bằng Regex chuẩn
                        if (preg_match('/from (\d+\.\d+\.\d+\.\d+)/', $line, $matches)) {
                            $ip = $matches[1];
                            
                            // Ghi vào bảng logs của hệ thống
                            SystemLog::firstOrCreate([
                                'ip_address' => $ip,
                                'message' => "Cảnh báo SSH: " . trim($line),
                                'level' => 'danger',
                                'source' => 'SSH Security'
                            ]);
                            $count++;
                        }
                    }
                    return back()->with('success', "Đã quét và đồng bộ $count bản ghi SSH Attack thành công!");
                }
            } catch (\Exception $e) {
                return back()->with('error', "Không thể đọc file auth.log. Yêu cầu cấp quyền đọc cho user www.");
            }
        }
        
        return back()->with('error', "Hệ điều hành không hỗ trợ tính năng này (Chỉ dành cho Linux).");
    }
}