<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\SystemLog;
use Carbon\Carbon;

class SystemLogController extends Controller
{
    public function index(Request $request)
    {
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

        // BIỂU ĐỒ CHART.JS (7 Ngày)
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
            'logs', 'chartLabels', 'chartBlocked', 'chartUnblocked', 'chartAlert', 'fromDate', 'toDate'
        ));
    }
}