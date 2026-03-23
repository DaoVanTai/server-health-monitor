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

        // 1. CHỨC NĂNG TÌM KIẾM & LỌC THEO LOẠI
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

        // 2. LỌC THEO THỜI GIAN
        if ($request->filled('from_date') && $request->filled('to_date')) {
            $from = $request->from_date . ' 00:00:00';
            $to = $request->to_date . ' 23:59:59';
            $query->whereBetween('created_at', [$from, $to]);
        }

        // 3. THỐNG KÊ ANALYTICS CẤP CAO
        $stats = [
            'total' => SystemLog::count(),
            'attack' => SystemLog::where('level', 'danger')->count(), // Tấn công (Đỏ)
            'alert' => SystemLog::where('level', 'warning')->count(), // Cảnh báo (Vàng)
            'system' => SystemLog::where('level', 'info')->count(),   // Hệ thống (Xanh)
        ];

        // 4. LẤY TẤT CẢ IP TẤN CÔNG VÀ BỊ BAN (Đỏ & Vàng)
        $topIps = SystemLog::select('ip_address', DB::raw('count(*) as total'))
            ->whereNotNull('ip_address')
            ->whereIn('level', ['danger', 'warning']) 
            ->groupBy('ip_address')
            ->orderByDesc('total')
            ->get(); 

        // 5. CHUẨN BỊ DỮ LIỆU CHO BIỂU ĐỒ CHART.JS (7 ngày gần nhất)
        $chartLabels = [];
        $chartAttack = [];
        $chartAlert = [];

        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i)->format('Y-m-d');
            $chartLabels[] = Carbon::now()->subDays($i)->format('d/m');
            
            $chartAttack[] = SystemLog::whereDate('created_at', $date)->where('level', 'danger')->count();
            $chartAlert[] = SystemLog::whereDate('created_at', $date)->where('level', 'warning')->count();
        }

        // 6. LẤY DANH SÁCH HIỂN THỊ CHÍNH
        $logs = $query->orderBy('created_at', 'desc')->paginate(15)->appends($request->query());

        return view('logs', compact('logs', 'stats', 'topIps', 'chartLabels', 'chartAttack', 'chartAlert'));
    }
}