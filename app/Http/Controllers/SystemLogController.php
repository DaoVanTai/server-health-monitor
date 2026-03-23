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

        // 1. TÌM KIẾM THEO TỪ KHÓA & MỨC ĐỘ
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

        // ==========================================
        // 2. KHÔI PHỤC BỘ LỌC KHOẢNG THỜI GIAN
        // ==========================================
        $fromDate = $request->input('from_date');
        $toDate = $request->input('to_date');

        if ($fromDate && $toDate) {
            $from = $fromDate . ' 00:00:00';
            $to = $toDate . ' 23:59:59';
            $query->whereBetween('created_at', [$from, $to]);
        }

        // 3. THỐNG KÊ ANALYTICS CẤP CAO (Cho biểu đồ và tổng quan)
        $stats = [
            'total' => SystemLog::count(),
            'attack' => SystemLog::where('level', 'danger')->count(), 
            'alert' => SystemLog::where('level', 'warning')->count(), 
            'system' => SystemLog::where('level', 'info')->count(),   
        ];

        // 4. LẤY TẤT CẢ IP TẤN CÔNG VÀ BỊ BAN
        $topIps = SystemLog::select('ip_address', DB::raw('count(*) as total'))
            ->whereNotNull('ip_address')
            ->whereIn('level', ['danger', 'warning']) 
            ->groupBy('ip_address')
            ->orderByDesc('total')
            ->get(); 

        // 5. CHUẨN BỊ DỮ LIỆU CHO BIỂU ĐỒ CHART.JS
        $chartLabels = [];
        $chartAttack = [];
        $chartAlert = [];

        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i)->format('Y-m-d');
            $chartLabels[] = Carbon::now()->subDays($i)->format('d/m');
            
            $chartAttack[] = SystemLog::whereDate('created_at', $date)->where('level', 'danger')->count();
            $chartAlert[] = SystemLog::whereDate('created_at', $date)->where('level', 'warning')->count();
        }

        // 6. LẤY TOÀN BỘ LOGS THEO ĐIỀU KIỆN LỌC (Không phân trang)
        $logs = $query->orderBy('created_at', 'desc')->get();

        return view('logs', compact(
            'logs', 'stats', 'topIps', 'chartLabels', 'chartAttack', 'chartAlert', 
            'fromDate', 'toDate'
        ));
    }
}