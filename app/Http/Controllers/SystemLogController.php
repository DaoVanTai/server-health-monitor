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
        // 2. LOGIC MỚI: 1 TRANG = 1 NGÀY
        // ==========================================
        // Lấy ngày hiện tại đang xem từ URL (Mặc định là Hôm nay)
        $viewDate = $request->input('view_date', Carbon::today()->format('Y-m-d'));

        // Lọc toàn bộ log chỉ trong ngày đó
        $query->whereDate('created_at', $viewDate);

        // 3. THỐNG KÊ ANALYTICS CẤP CAO (Thống kê cho toàn hệ thống)
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

        // 6. LẤY LOGS CHO NGÀY ĐANG XEM (Dùng get thay vì paginate)
        $logs = $query->orderBy('created_at', 'desc')->get();

        // 7. TÍNH TOÁN NGÀY TRƯỚC VÀ NGÀY SAU ĐỂ LÀM NÚT NEXT/PREV
        $currentDateObj = Carbon::parse($viewDate);
        $prevDate = $currentDateObj->copy()->subDay()->format('Y-m-d');
        $nextDate = $currentDateObj->copy()->addDay()->format('Y-m-d');
        $isToday = $currentDateObj->isToday();

        return view('logs', compact(
            'logs', 'stats', 'topIps', 'chartLabels', 'chartAttack', 'chartAlert', 
            'viewDate', 'prevDate', 'nextDate', 'isToday'
        ));
    }
}