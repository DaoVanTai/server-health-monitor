<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\SystemLog;

class SystemLogController extends Controller
{
    public function index(Request $request)
    {
        $query = SystemLog::query();

        // 1. CHỨC NĂNG TÌM KIẾM TỔNG HỢP (Search)
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('message', 'like', "%{$search}%")
                  ->orWhere('source', 'like', "%{$search}%")
                  ->orWhere('ip_address', 'like', "%{$search}%");
            });
        }

        // 2. CHỨC NĂNG LỌC THEO MỨC ĐỘ (Filter)
        if ($request->filled('level') && $request->level !== 'all') {
            $query->where('level', $request->level);
        }

        // 3. THỐNG KÊ ANALYTICS (Để vẽ biểu đồ/số liệu)
        $stats = [
            'total' => SystemLog::count(),
            'danger' => SystemLog::where('level', 'danger')->count(),
            'warning' => SystemLog::where('level', 'warning')->count(),
            'info' => SystemLog::where('level', 'info')->count(),
        ];

        // Lấy dữ liệu mới nhất, phân trang 15 dòng/trang để web không bị giật
        $logs = $query->orderBy('created_at', 'desc')->paginate(15)->appends($request->query());

        return view('logs', compact('logs', 'stats'));
    }
}