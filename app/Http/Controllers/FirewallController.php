<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Blacklist;
use App\Models\SystemLog; // <-- Thêm thư viện Log
use Carbon\Carbon;

class FirewallController extends Controller
{
    // Hiển thị trang Firewall (Đã nâng cấp Thống kê & Timeline)
    public function index()
    {
        $blacklists = Blacklist::orderBy('created_at', 'desc')->get();
        
        // 1. THỐNG KÊ TẤN CÔNG (Statistics)
        $totalAttacks = Blacklist::count();
        $todayAttacks = Blacklist::whereDate('created_at', Carbon::today())->count();
        $autoBanned = Blacklist::where('reason', 'like', '%Auto-ban%')
                               ->orWhere('reason', 'like', '%Flood%')
                               ->count();

        // 2. TIMELINE TẤN CÔNG (Lấy 10 sự kiện mới nhất)
        $timelineEvents = Blacklist::orderBy('created_at', 'desc')->take(10)->get();

        return view('firewall', compact('blacklists', 'totalAttacks', 'todayAttacks', 'autoBanned', 'timelineEvents'));
    }

    // Xử lý chặn IP
    public function blockIP(Request $request)
    {
        $request->validate(['ip_address' => 'required|ip']);

        $ip = $request->ip_address;
        $reason = $request->reason ?? 'Manual Blocked by Admin';

        // 1. Lưu vào Database Blacklist
        Blacklist::create([
            'ip_address' => $ip,
            'reason' => $reason,
            'status' => 'blocked'
        ]);

        // 2. GHI VÀO LOG HỆ THỐNG (REAL LOG)
        SystemLog::create([
            'level' => 'danger',
            'source' => 'Manual Firewall',
            'message' => "Quản trị viên đã CHẶN thủ công IP. Lý do: $reason",
            'ip_address' => $ip,
        ]);

        // 3. Chặn thật trên hệ thống Linux (Yêu cầu sudo ufw)
        if (PHP_OS_FAMILY === 'Linux') {
            shell_exec("sudo ufw deny from $ip");
        }

        return back()->with('success', "IP $ip đã bị đưa vào danh sách đen!");
    }

    // Xử lý gỡ chặn IP
    public function unblockIP($id)
    {
        $item = Blacklist::findOrFail($id);
        
        // GHI VÀO LOG HỆ THỐNG (REAL LOG)
        SystemLog::create([
            'level' => 'info',
            'source' => 'Manual Firewall',
            'message' => "Quản trị viên đã GỠ CHẶN an toàn cho IP này.",
            'ip_address' => $item->ip_address,
        ]);
        
        // Gỡ lệnh chặn trên Linux
        if (PHP_OS_FAMILY === 'Linux') {
            shell_exec("sudo ufw delete deny from {$item->ip_address}");
        }
        
        $item->delete();
        return back()->with('success', "Đã gỡ chặn IP thành công!");
    }
}