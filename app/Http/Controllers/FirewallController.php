<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Blacklist;
use App\Models\SystemLog;
use Carbon\Carbon;

class FirewallController extends Controller
{
    public function index()
    {
        $blacklists = Blacklist::orderBy('created_at', 'desc')->get();
        
        $totalAttacks = Blacklist::count();
        $todayAttacks = Blacklist::whereDate('created_at', Carbon::today())->count();
        $autoBanned = Blacklist::where('reason', 'like', '%Auto-ban%')
                               ->orWhere('reason', 'like', '%Flood%')
                               ->count();

        $timelineEvents = Blacklist::orderBy('created_at', 'desc')->take(10)->get();

        return view('firewall', compact('blacklists', 'totalAttacks', 'todayAttacks', 'autoBanned', 'timelineEvents'));
    }

    // Xử lý chặn IP
    public function blockIP(Request $request)
    {
        $request->validate(['ip_address' => 'required|ip']);

        $ip = $request->ip_address;
        $reason = $request->reason ?? 'Manual Blocked by Admin';

        // ==========================================
        // 1. CHỐNG TRÙNG LẶP (Chặn 1 lần là xong)
        // ==========================================
        if (Blacklist::where('ip_address', $ip)->exists()) {
            return back()->with('error', "IP $ip đã bị chặn trước đó rồi, không thể chặn lại!");
        }

        // 2. Lưu vào Database Blacklist
        Blacklist::create([
            'ip_address' => $ip,
            'reason' => $reason,
            'status' => 'blocked'
        ]);

        // 3. GHI VÀO LOG HỆ THỐNG (REAL LOG)
        SystemLog::create([
            'level' => 'danger',
            'source' => 'Manual Firewall',
            'message' => "Quản trị viên đã CHẶN thủ công IP. Lý do: $reason",
            'ip_address' => $ip,
        ]);

        // 4. Chặn thật trên hệ thống Linux (Bảo mật bằng escapeshellarg)
        if (PHP_OS_FAMILY === 'Linux') {
            try {
                shell_exec("sudo ufw deny from " . escapeshellarg($ip));
            } catch (\Exception $e) {
                \Log::error("UFW Block Error: " . $e->getMessage());
            }
        }

        return back()->with('success', "IP $ip đã bị đưa vào danh sách đen!");
    }

    // Xử lý gỡ chặn IP
    public function unblockIP($id)
    {
        $item = Blacklist::findOrFail($id);
        
        // GHI VÀO LOG HỆ THỐNG
        SystemLog::create([
            'level' => 'info',
            'source' => 'Manual Firewall',
            'message' => "Quản trị viên đã GỠ CHẶN an toàn cho IP này.",
            'ip_address' => $item->ip_address,
        ]);
        
        // Gỡ lệnh chặn trên Linux
        if (PHP_OS_FAMILY === 'Linux') {
            try {
                shell_exec("sudo ufw delete deny from " . escapeshellarg($item->ip_address));
            } catch (\Exception $e) {
                \Log::error("UFW Unblock Error: " . $e->getMessage());
            }
        }
        
        $item->delete();
        return back()->with('success', "Đã gỡ chặn IP thành công!");
    }
}