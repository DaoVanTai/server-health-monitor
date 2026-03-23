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
            'attack' => SystemLog::where('level', 'danger')->count(), 
            'alert' => SystemLog::where('level', 'warning')->count(), 
            'system' => SystemLog::where('level', 'info')->count(),   
        ];

        // 6.2 THỐNG KÊ IP TẤN CÔNG NHIỀU NHẤT
        $topIpsQuery = SystemLog::select('ip_address', DB::raw('count(*) as total'))
            ->whereNotNull('ip_address')
            ->whereIn('level', ['danger', 'warning']) 
            ->groupBy('ip_address')
            ->orderByDesc('total');
        if ($fromDate && $toDate) $topIpsQuery->whereBetween('created_at', [$from, $to]);
        $topIps = $topIpsQuery->get(); 

        // 6.1 THỐNG KÊ SỐ LẦN CẢNH BÁO (Gom nhóm theo Nguồn cảnh báo)
        $alertDetailsQuery = SystemLog::select('source', DB::raw('count(*) as total'))
            ->where('level', 'warning')
            ->groupBy('source')
            ->orderByDesc('total');
        if ($fromDate && $toDate) $alertDetailsQuery->whereBetween('created_at', [$from, $to]);
        $alertDetails = $alertDetailsQuery->get();

        // 7. ATTACK TIMELINE (Lấy sự kiện nguy hiểm, Sắp xếp TĂNG DẦN theo thời gian)
        $timelineQuery = SystemLog::whereIn('level', ['danger', 'warning'])
            ->orderBy('created_at', 'asc');
        if ($fromDate && $toDate) $timelineQuery->whereBetween('created_at', [$from, $to]);
        $attackTimeline = $timelineQuery->get();

        // 6.3 BIỂU ĐỒ CHART.JS (7 Ngày)
        $chartLabels = [];
        $chartAttack = [];
        $chartAlert = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i)->format('Y-m-d');
            $chartLabels[] = Carbon::now()->subDays($i)->format('d/m');
            $chartAttack[] = SystemLog::whereDate('created_at', $date)->where('level', 'danger')->count();
            $chartAlert[] = SystemLog::whereDate('created_at', $date)->where('level', 'warning')->count();
        }

        // LẤY BẢNG LOGS CHÍNH
        $logs = $query->orderBy('created_at', 'desc')->get();

        return view('logs', compact(
            'logs', 'stats', 'topIps', 'chartLabels', 'chartAttack', 'chartAlert', 
            'fromDate', 'toDate', 'alertDetails', 'attackTimeline'
        ));
    }
}