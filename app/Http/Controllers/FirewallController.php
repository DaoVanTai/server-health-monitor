<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Blacklist;

class FirewallController extends Controller
{
    // Hiển thị trang Firewall
    public function index()
    {
        $blacklists = Blacklist::orderBy('created_at', 'desc')->get();
        return view('firewall', compact('blacklists'));
    }

    // Xử lý chặn IP
    public function blockIP(Request $request)
    {
        $request->validate(['ip_address' => 'required|ip']);

        $ip = $request->ip_address;
        $reason = $request->reason ?? 'Manual Blocked by Admin';

        // 1. Lưu vào Database
        Blacklist::create([
            'ip_address' => $ip,
            'reason' => $reason,
            'status' => 'blocked'
        ]);

        // 2. Chặn thật trên hệ thống Linux (Yêu cầu sudo ufw)
        shell_exec("sudo ufw deny from $ip");

        return back()->with('success', "IP $ip đã bị đưa vào danh sách đen!");
    }

    // Xử lý gỡ chặn IP
    public function unblockIP($id)
    {
        $item = Blacklist::findOrFail($id);
        
        // Gỡ lệnh chặn trên Linux
        shell_exec("sudo ufw delete deny from {$item->ip_address}");
        
        $item->delete();
        return back()->with('success', "Đã gỡ chặn IP thành công!");
    }
}