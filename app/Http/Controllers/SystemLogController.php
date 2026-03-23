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
            // Khi lọc theo Attack (danger), lấy cả log tấn công (đỏ) và gỡ chặn (xanh lá)
            if ($request->level === 'danger') {
                $query->whereIn('level', ['danger', 'success']);
            } else {
                $query->where('level', $request->level);
            }
        }

        // 2. LỌC KHOẢNG THỜI GIAN
        $fromDate = $request->input('from_date');
        $toDate = $request->input('to_date');

        if ($fromDate && $toDate) {
            $from = $fromDate . ' 00:00:00';
            $to = $toDate . ' 23:59:59';
            $query->whereBetween('created_at', [$from, $to]);
        }

        // 3. THỐNG KÊ TỔNG QUAN
        $stats = [
            'total' => SystemLog::count(),
            'attack' => SystemLog::whereIn('level', ['danger', 'success'])->count(), // Gộp đỏ và xanh lá
            'alert' => SystemLog::where('level', 'warning')->count(), // Vàng
            'system' => SystemLog::where('level', 'info')->count(),   // Xanh dương
        ];

        // TOP IP TẤN CÔNG (Chỉ lấy những IP thực sự tấn công - đỏ)
        $topIpsQuery = SystemLog::select('ip_address', DB::raw('count(*) as total'))
            ->whereNotNull('ip_address')
            ->where('level', 'danger') 
            ->groupBy('ip_address')
            ->orderByDesc('total');
        if ($fromDate && $toDate) $topIpsQuery->whereBetween('created_at', [$from, $to]);
        $topIps = $topIpsQuery->get(); 

        // THỐNG KÊ SỐ LẦN CẢNH BÁO (Chỉ lấy cảnh báo - vàng)
        $alertDetailsQuery = SystemLog::select('source', DB::raw('count(*) as total'))
            ->where('level', 'warning')
            ->groupBy('source')
            ->orderByDesc('total');
        if ($fromDate && $toDate) $alertDetailsQuery->whereBetween('created_at', [$from, $to]);
        $alertDetails = $alertDetailsQuery->get();

        // ATTACK TIMELINE (Lấy Tấn công, Gỡ chặn và Cảnh báo để hiển thị log sự kiện)
        $timelineQuery = SystemLog::whereIn('level', ['danger', 'success', 'warning'])
            ->orderBy('created_at', 'desc');
        if ($fromDate && $toDate) $timelineQuery->whereBetween('created_at', [$from, $to]);
        $attackTimeline = $timelineQuery->get();

        // BIỂU ĐỒ CHART.JS (7 Ngày)
        $chartLabels = [];
        $chartAttack = [];
        $chartAlert = [];
        $chartSystem = []; 
        
        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i)->format('Y-m-d');
            $chartLabels[] = Carbon::now()->subDays($i)->format('d/m');
            // Biểu đồ Security (Attack) = số cuộc tấn công + số lần gỡ chặn
            $chartAttack[] = SystemLog::whereDate('created_at', $date)->whereIn('level', ['danger', 'success'])->count();
            $chartAlert[] = SystemLog::whereDate('created_at', $date)->where('level', 'warning')->count();
            $chartSystem[] = SystemLog::whereDate('created_at', $date)->where('level', 'info')->count();
        }

        // LẤY BẢNG LOGS CHÍNH
        $logs = $query->orderBy('created_at', 'desc')->get();

        return view('logs', compact(
            'logs', 'stats', 'topIps', 'chartLabels', 'chartAttack', 'chartAlert', 'chartSystem', 
            'fromDate', 'toDate', 'alertDetails', 'attackTimeline'
        ));
    }
}