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
        // QUAN TRỌNG: Chỉ lấy Đỏ (Blocked), Xanh lá (Unblocked) và Vàng (Alert). 
        // Lệnh này sẽ tự động giấu hết các log System Xanh dương cũ!
        $query = SystemLog::whereIn('level', ['danger', 'success', 'warning']);

        // 1. TÌM KIẾM THEO TỪ KHÓA
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('message', 'like', "%{$search}%")
                  ->orWhere('source', 'like', "%{$search}%")
                  ->orWhere('ip_address', 'like', "%{$search}%");
            });
        }

        // LỌC THEO MỨC ĐỘ
        if ($request->filled('level') && $request->level !== 'all') {
            $query->where('level', $request->level);
        }

        // 2. LỌC KHOẢNG THỜI GIAN
        $fromDate = $request->input('from_date');
        $toDate = $request->input('to_date');

        if ($fromDate && $toDate) {
            $from = $fromDate . ' 00:00:00';
            $to = $toDate . ' 23:59:59';
            $query->whereBetween('created_at', [$from, $to]);
        }

        // TOP IP BỊ CHẶN (Chỉ lấy màu đỏ)
        $topIpsQuery = SystemLog::select('ip_address', DB::raw('count(*) as total'))
            ->whereNotNull('ip_address')
            ->where('level', 'danger') 
            ->groupBy('ip_address')
            ->orderByDesc('total');
        if ($fromDate && $toDate) $topIpsQuery->whereBetween('created_at', [$from, $to]);
        $topIps = $topIpsQuery->get(); 

        // THỐNG KÊ SỐ LẦN CẢNH BÁO (Chỉ lấy màu vàng)
        $alertDetailsQuery = SystemLog::select('source', DB::raw('count(*) as total'))
            ->where('level', 'warning')
            ->groupBy('source')
            ->orderByDesc('total');
        if ($fromDate && $toDate) $alertDetailsQuery->whereBetween('created_at', [$from, $to]);
        $alertDetails = $alertDetailsQuery->get();

        // TIMELINE SỰ KIỆN
        $timelineQuery = SystemLog::whereIn('level', ['danger', 'success', 'warning'])
            ->orderBy('created_at', 'desc');
        if ($fromDate && $toDate) $timelineQuery->whereBetween('created_at', [$from, $to]);
        $attackTimeline = $timelineQuery->get();

        // BIỂU ĐỒ CHART.JS (7 Ngày) - Tách riêng Blocked và Unblocked
        $chartLabels = [];
        $chartBlocked = [];
        $chartUnblocked = [];
        $chartAlert = [];
        
        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i)->format('Y-m-d');
            $chartLabels[] = Carbon::now()->subDays($i)->format('d/m');
            
            $chartBlocked[] = SystemLog::whereDate('created_at', $date)->where('level', 'danger')->count();
            $chartUnblocked[] = SystemLog::whereDate('created_at', $date)->where('level', 'success')->count();
            $chartAlert[] = SystemLog::whereDate('created_at', $date)->where('level', 'warning')->count();
        }

        // LẤY BẢNG LOGS CHÍNH
        $logs = $query->orderBy('created_at', 'desc')->get();

        return view('logs', compact(
            'logs', 'topIps', 'chartLabels', 'chartBlocked', 'chartUnblocked', 'chartAlert', 
            'fromDate', 'toDate', 'alertDetails', 'attackTimeline'
        ));
    }
}