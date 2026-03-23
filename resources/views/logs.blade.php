<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Log & Analytics - Security Center</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --bg-main: #0b1120; 
            --bg-card: #111827; 
            --neon-red: #ef4444; 
            --neon-blue: #3b82f6;
            --neon-orange: #f59e0b;
            --neon-green: #10b981;
            --text-main: #f3f4f6;
            --text-muted: #9ca3af;
            --border-color: #1f2937;
        }
        body { background: var(--bg-main); color: var(--text-main); font-family: 'Segoe UI', sans-serif; margin: 0; display: flex; min-height: 100vh; }
        
        /* --- SIDEBAR SYNC --- */
        .sidebar { width: 70px; background-color: #0f172a; border-right: 1px solid var(--border-color); display: flex; flex-direction: column; padding: 20px 0; transition: width 0.3s; overflow: hidden; white-space: nowrap; position: fixed; height: 100vh; z-index: 1000; }
        .sidebar:hover { width: 220px; box-shadow: 10px 0 30px rgba(0,0,0,0.5); }
        .sidebar-item { width: 100%; padding: 15px 0; display: flex; align-items: center; color: var(--text-muted); text-decoration: none; transition: all 0.2s; border-left: 3px solid transparent; }
        .sidebar-icon-wrapper { min-width: 70px; display: flex; justify-content: center; align-items: center; }
        .sidebar-item span { opacity: 0; transform: translateX(-10px); transition: all 0.3s; font-size: 14px; font-weight: 500; }
        .sidebar:hover .sidebar-item span { opacity: 1; transform: translateX(0); }
        .sidebar-item.active { color: var(--neon-blue); border-left: 3px solid var(--neon-blue); background: rgba(59, 130, 246, 0.05); }
        .sidebar-item:hover { color: var(--text-main); }

        /* --- CONTENT & GRID --- */
        .main-content { margin-left: 70px; padding: 40px; width: calc(100% - 70px); box-sizing: border-box; }
        h1 { color: var(--neon-blue); letter-spacing: 2px; text-transform: uppercase; margin-top: 0; }
        
        /* --- THỐNG KÊ (STATS) --- */
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 30px; }
        .stat-card { background: var(--bg-card); padding: 20px; border-radius: 12px; border: 1px solid var(--border-color); display: flex; align-items: center; gap: 20px; }
        .stat-icon { width: 50px; height: 50px; border-radius: 10px; display: flex; justify-content: center; align-items: center; font-size: 20px; }
        .bg-red { background: rgba(239, 68, 68, 0.1); color: var(--neon-red); }
        .bg-orange { background: rgba(245, 158, 11, 0.1); color: var(--neon-orange); }
        .bg-blue { background: rgba(59, 130, 246, 0.1); color: var(--neon-blue); }
        .bg-green { background: rgba(16, 185, 129, 0.1); color: var(--neon-green); }
        .stat-info h3 { margin: 0; font-size: 24px; color: white; }
        .stat-info p { margin: 5px 0 0 0; font-size: 13px; color: var(--text-muted); text-transform: uppercase; }

        /* --- BẢNG LOGS --- */
        .log-card { background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 12px; padding: 25px; }
        
        /* --- FILTER BAR --- */
        .filter-bar { display: flex; gap: 15px; margin-bottom: 20px; background: rgba(255,255,255,0.02); padding: 15px; border-radius: 8px; border: 1px solid #1f2937; }
        .filter-input { background: #0b1120; border: 1px solid #374151; color: white; padding: 10px 15px; border-radius: 6px; flex: 1; outline: none; }
        .filter-input:focus { border-color: var(--neon-blue); }
        .filter-select { background: #0b1120; border: 1px solid #374151; color: white; padding: 10px 15px; border-radius: 6px; outline: none; min-width: 150px; }
        .btn-search { background: var(--neon-blue); color: white; border: none; padding: 10px 25px; border-radius: 6px; cursor: pointer; font-weight: bold; transition: 0.3s; }
        .btn-search:hover { box-shadow: 0 0 15px rgba(59,130,246,0.4); }

        /* --- TABLE --- */
        .log-table { width: 100%; border-collapse: collapse; }
        .log-table th { text-align: left; color: #9ca3af; padding: 15px 12px; border-bottom: 1px solid #374151; font-size: 13px; text-transform: uppercase;}
        .log-table td { padding: 15px 12px; border-bottom: 1px solid rgba(255,255,255,0.03); font-size: 14px; color: #cbd5e1; }
        .log-table tr:hover td { background: rgba(255,255,255,0.01); }
        
        /* LEVEL BADGES */
        .badge { padding: 4px 10px; border-radius: 4px; font-size: 12px; font-weight: bold; text-transform: uppercase; letter-spacing: 0.5px; }
        .badge-danger { background: rgba(239, 68, 68, 0.1); color: var(--neon-red); border: 1px solid rgba(239, 68, 68, 0.2); }
        .badge-warning { background: rgba(245, 158, 11, 0.1); color: var(--neon-orange); border: 1px solid rgba(245, 158, 11, 0.2); }
        .badge-info { background: rgba(59, 130, 246, 0.1); color: var(--neon-blue); border: 1px solid rgba(59, 130, 246, 0.2); }
        
        /* PAGINATION */
        .pagination-container { margin-top: 20px; display: flex; justify-content: space-between; align-items: center; color: var(--text-muted); font-size: 14px; }
        .pagination-links { display: flex; gap: 5px; list-style: none; padding: 0; margin: 0; }
        .pagination-links a, .pagination-links span { padding: 8px 12px; background: #1f2937; color: white; text-decoration: none; border-radius: 4px; border: 1px solid #374151; transition: 0.2s; }
        .pagination-links a:hover { background: var(--neon-blue); border-color: var(--neon-blue); }
        .pagination-links .active span { background: var(--neon-blue); border-color: var(--neon-blue); color: white; }
    </style>
</head>
<body>
    <aside class="sidebar">
        <a href="{{ route('monitor') }}" class="sidebar-item"><div class="sidebar-icon-wrapper"><i class="fas fa-desktop"></i></div><span>Dashboard</span></a>
        <a href="{{ route('network.index') }}" class="sidebar-item"><div class="sidebar-icon-wrapper"><i class="fas fa-network-wired"></i></div><span>Network Center</span></a>
        <a href="{{ route('firewall.index') }}" class="sidebar-item"><div class="sidebar-icon-wrapper"><i class="fas fa-shield-alt"></i></div><span>Security</span></a>
        <a href="{{ route('ai.index') }}" class="sidebar-item"><div class="sidebar-icon-wrapper"><i class="fas fa-brain"></i></div><span>AI Insight</span></a>
        <a href="{{ route('logs.index') }}" class="sidebar-item active"><div class="sidebar-icon-wrapper"><i class="fas fa-clipboard-list"></i></div><span>Log & Analytics</span></a>
    </aside>

    <main class="main-content">
        <h1>📊 System Logs & Analytics</h1>
        <p style="color: #9ca3af; margin-bottom: 30px;">Trung tâm Phân tích Dữ liệu và Truy vết Sự kiện Hệ thống</p>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon bg-blue"><i class="fas fa-database"></i></div>
                <div class="stat-info">
                    <h3>{{ number_format($stats['total']) }}</h3>
                    <p>Tổng số sự kiện (Logs)</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon bg-red"><i class="fas fa-radiation"></i></div>
                <div class="stat-info">
                    <h3>{{ number_format($stats['danger']) }}</h3>
                    <p>Nguy hiểm (Danger)</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon bg-orange"><i class="fas fa-exclamation-triangle"></i></div>
                <div class="stat-info">
                    <h3>{{ number_format($stats['warning']) }}</h3>
                    <p>Cảnh báo (Warning)</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon bg-green"><i class="fas fa-info-circle"></i></div>
                <div class="stat-info">
                    <h3>{{ number_format($stats['info']) }}</h3>
                    <p>Thông tin (Info)</p>
                </div>
            </div>
        </div>

        <div class="log-card">
            <form action="{{ route('logs.index') }}" method="GET" class="filter-bar">
                <input type="text" name="search" class="filter-input" placeholder="Tìm kiếm nội dung, nguồn hoặc IP..." value="{{ request('search') }}">
                
                <select name="level" class="filter-select">
                    <option value="all" {{ request('level') == 'all' ? 'selected' : '' }}>Tất cả mức độ</option>
                    <option value="danger" {{ request('level') == 'danger' ? 'selected' : '' }}>Nguy hiểm (Danger)</option>
                    <option value="warning" {{ request('level') == 'warning' ? 'selected' : '' }}>Cảnh báo (Warning)</option>
                    <option value="info" {{ request('level') == 'info' ? 'selected' : '' }}>Thông tin (Info)</option>
                </select>

                <button type="submit" class="btn-search"><i class="fas fa-search"></i> PHÂN TÍCH</button>
                <a href="{{ route('logs.index') }}" class="btn-search" style="background: #374151; text-decoration: none; text-align: center;"><i class="fas fa-sync"></i> Xóa Lọc</a>
            </form>

            <table class="log-table">
                <thead>
                    <tr>
                        <th>Thời gian (Timestamp)</th>
                        <th>Mức độ</th>
                        <th>Nguồn (Source)</th>
                        <th>Địa chỉ IP</th>
                        <th>Nội dung sự kiện (Message)</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($logs as $log)
                    <tr>
                        <td style="color: var(--text-muted); font-family: monospace;">{{ $log->created_at->format('Y-m-d H:i:s') }}</td>
                        <td>
                            <span class="badge badge-{{ $log->level }}">
                                @if($log->level == 'danger') <i class="fas fa-times-circle"></i>
                                @elseif($log->level == 'warning') <i class="fas fa-exclamation-triangle"></i>
                                @else <i class="fas fa-info-circle"></i>
                                @endif
                                {{ $log->level }}
                            </span>
                        </td>
                        <td style="font-weight: bold;">{{ $log->source }}</td>
                        <td style="font-family: monospace; color: var(--neon-blue);">{{ $log->ip_address ?? 'N/A' }}</td>
                        <td>{{ $log->message }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" style="text-align: center; color: var(--text-muted); padding: 40px;">
                            <i class="fas fa-folder-open" style="font-size: 30px; margin-bottom: 10px; display: block;"></i>
                            Không tìm thấy dữ liệu Log nào phù hợp.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>

            @if($logs->hasPages())
            <div class="pagination-container">
                <div>Đang hiển thị trang {{ $logs->currentPage() }} / {{ $logs->lastPage() }}</div>
                <div class="pagination-links">
                    {{ $logs->links('pagination::bootstrap-4') }}
                </div>
            </div>
            @endif
        </div>
    </main>
</body>
</html>