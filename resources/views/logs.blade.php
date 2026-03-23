<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Log & Analytics - Security Center</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        :root {
            --bg-main: #0b1120; --bg-card: #111827; --neon-red: #ef4444; 
            --neon-blue: #3b82f6; --neon-yellow: #eab308; --text-main: #f3f4f6;
            --text-muted: #9ca3af; --border-color: #1f2937;
        }
        body { background: var(--bg-main); color: var(--text-main); font-family: 'Segoe UI', sans-serif; margin: 0; display: flex; min-height: 100vh; }
        
        .sidebar { width: 70px; background-color: #0f172a; border-right: 1px solid var(--border-color); display: flex; flex-direction: column; padding: 20px 0; transition: width 0.3s; overflow: hidden; white-space: nowrap; position: fixed; height: 100vh; z-index: 1000; }
        .sidebar:hover { width: 220px; box-shadow: 10px 0 30px rgba(0,0,0,0.5); }
        .sidebar-item { width: 100%; padding: 15px 0; display: flex; align-items: center; color: var(--text-muted); text-decoration: none; transition: all 0.2s; border-left: 3px solid transparent; }
        .sidebar-icon-wrapper { min-width: 70px; display: flex; justify-content: center; align-items: center; }
        .sidebar-item span { opacity: 0; transform: translateX(-10px); transition: all 0.3s; font-size: 14px; font-weight: 500; }
        .sidebar:hover .sidebar-item span { opacity: 1; transform: translateX(0); }
        .sidebar-item.active { color: var(--neon-blue); border-left: 3px solid var(--neon-blue); background: rgba(59, 130, 246, 0.05); }

        .main-content { margin-left: 70px; padding: 40px; width: calc(100% - 70px); box-sizing: border-box; }
        h1 { color: var(--neon-blue); letter-spacing: 2px; text-transform: uppercase; margin-top: 0; display: flex; justify-content: space-between; align-items: center;}
        
        .card { background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 12px; padding: 25px; margin-bottom: 25px; }
        
        /* Analytics Grid */
        .analytics-grid { display: grid; grid-template-columns: 2fr 1fr; gap: 25px; margin-bottom: 25px; }
        @media (max-width: 1200px) { .analytics-grid { grid-template-columns: 1fr; } }

        /* Filter Bar */
        .filter-bar { display: flex; flex-wrap: wrap; gap: 15px; margin-bottom: 20px; background: rgba(255,255,255,0.02); padding: 15px; border-radius: 8px; border: 1px solid #1f2937; align-items: center;}
        .filter-input { background: #0b1120; border: 1px solid #374151; color: white; padding: 10px 15px; border-radius: 6px; outline: none; }
        .filter-input:focus { border-color: var(--neon-blue); }
        .btn-action { background: var(--neon-blue); color: white; border: none; padding: 10px 20px; border-radius: 6px; cursor: pointer; font-weight: bold; transition: 0.3s; text-decoration: none;}
        .btn-action:hover { opacity: 0.8; }
        .btn-danger { background: var(--neon-red); }

        /* --- CSS MỚI: Tùy chỉnh Icon Lịch --- */
        input[type="date"] {
            position: relative;
            padding-right: 15px;
        }
        input[type="date"]::-webkit-calendar-picker-indicator {
            filter: invert(1); /* Đổi icon thành màu trắng */
            transform: scale(1.4); /* Phóng to icon 40% */
            cursor: pointer;
            opacity: 0.6;
            transition: 0.2s;
            margin-left: auto; /* Đẩy sang sát mép phải */
        }
        input[type="date"]::-webkit-calendar-picker-indicator:hover {
            opacity: 1;
            transform: scale(1.6); /* Nổi to hơn khi di chuột */
        }

        /* --- CSS MỚI: Thanh cuộn mượt mà --- */
        .custom-scrollbar::-webkit-scrollbar { width: 6px; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: #374151; border-radius: 10px; }
        .custom-scrollbar::-webkit-scrollbar-track { background: transparent; }

        /* Table Cơ bản */
        .log-table { width: 100%; border-collapse: collapse; }
        .log-table th { text-align: left; color: #9ca3af; padding: 15px; border-bottom: 1px solid #374151; font-size: 13px; }
        .log-table td { padding: 15px; border-bottom: 1px solid rgba(255,255,255,0.03); font-size: 14px; }
        
        /* --- CSS MỚI: Hiệu ứng Hover Nổi bật cho các dòng --- */
        .log-table tbody tr { transition: all 0.3s ease; }
        .log-table tbody tr:hover {
            background-color: rgba(59, 130, 246, 0.08); /* Highlight nền xanh nhẹ */
            box-shadow: inset 4px 0 0 var(--neon-blue); /* Đường kẻ viền nổi bên trái */
            cursor: pointer;
        }
        
        /* Hiệu ứng Hover riêng cho Bảng Top IP (Màu Đỏ) */
        .top-ip-table tbody tr:hover {
            background-color: rgba(239, 68, 68, 0.1); 
            box-shadow: inset 4px 0 0 var(--neon-red);
        }

        /* Badges */
        .badge { padding: 4px 10px; border-radius: 4px; font-size: 12px; font-weight: bold; text-transform: uppercase; }
        .badge-danger { color: var(--neon-red); border: 1px solid var(--neon-red); } 
        .badge-warning { color: var(--neon-yellow); border: 1px solid var(--neon-yellow); } 
        .badge-info { color: var(--neon-blue); border: 1px solid var(--neon-blue); } 
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
        <h1>
            <span>📊 Phân Tích Sự Kiện (Analytics)</span>
            <form action="{{ route('logs.sync_ssh') }}" method="POST" style="display:inline;">
                @csrf
                <button type="submit" class="btn-action btn-danger"><i class="fas fa-sync fa-spin"></i> Đồng bộ Log SSH (auth.log)</button>
            </form>
        </h1>
        
        @if(session('success')) <div style="color: #10b981; margin-bottom: 15px;"><i class="fas fa-check"></i> {{ session('success') }}</div> @endif
        @if(session('error')) <div style="color: var(--neon-red); margin-bottom: 15px;"><i class="fas fa-times"></i> {{ session('error') }}</div> @endif

        <div class="analytics-grid">
            <div class="card">
                <h3 style="margin-top:0; color:var(--text-muted);"><i class="fas fa-chart-line"></i> Biểu Đồ Tấn Công (7 Ngày)</h3>
                <canvas id="attackChart" height="100"></canvas>
            </div>

            <div class="card" style="display: flex; flex-direction: column;">
                <h3 style="margin-top:0; color:var(--neon-red);"><i class="fas fa-skull-crossbones"></i> Bảng Xếp Hạng IP Tấn Công</h3>
                
                <div class="custom-scrollbar" style="flex: 1; overflow-y: auto; max-height: 230px; padding-right: 5px;">
                    <table class="log-table top-ip-table">
                        <thead>
                            <tr>
                                <th style="position: sticky; top: 0; background: var(--bg-card); z-index: 1;">IP ADDRESS</th>
                                <th style="position: sticky; top: 0; background: var(--bg-card); z-index: 1;">ATTEMPTS</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($topIps as $top)
                            <tr>
                                <td style="color: var(--neon-red); font-family: monospace;">{{ $top->ip_address }}</td>
                                <td style="font-weight:bold;">{{ $top->total }} lần</td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="2" style="text-align: center; color: var(--text-muted);">Chưa có dữ liệu tấn công.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="card">
            <form action="{{ route('logs.index') }}" method="GET" class="filter-bar">
                <input type="text" name="search" class="filter-input" placeholder="Tìm theo IP, Nội dung..." value="{{ request('search') }}" style="flex:1;">
                
                <select name="level" class="filter-input" onchange="this.form.submit()">
                    <option value="all" {{ request('level') == 'all' ? 'selected' : '' }}>Tất cả các loại Log</option>
                    <option value="danger" {{ request('level') == 'danger' ? 'selected' : '' }}>🔴 Tấn công (Attack)</option>
                    <option value="warning" {{ request('level') == 'warning' ? 'selected' : '' }}>🟡 Cảnh báo (Alert)</option>
                    <option value="info" {{ request('level') == 'info' ? 'selected' : '' }}>🔵 Hệ thống (System)</option>
                </select>

                <input type="date" name="from_date" class="filter-input" value="{{ request('from_date') }}" title="Từ ngày">
                <span style="color: var(--text-muted);">-</span>
                <input type="date" name="to_date" class="filter-input" value="{{ request('to_date') }}" title="Đến ngày">

                <button type="submit" class="btn-action"><i class="fas fa-search"></i> LỌC DỮ LIỆU</button>
            </form>

            <table class="log-table">
                <thead>
                    <tr>
                        <th>THỜI GIAN (TIME)</th>
                        <th>LOẠI (TYPE)</th>
                        <th>NGUỒN (SOURCE)</th>
                        <th>NỘI DUNG (MESSAGE)</th>
                        <th>IP ADDRESS</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($logs as $log)
                    <tr>
                        <td style="color: var(--text-muted); font-size: 13px;">{{ $log->created_at->format('H:i:s d/m/Y') }}</td>
                        <td>
                            <span class="badge badge-{{ $log->level }}">
                                @if($log->level == 'danger') Attack
                                @elseif($log->level == 'warning') Alert
                                @else System
                                @endif
                            </span>
                        </td>
                        <td style="color: #cbd5e1;">{{ $log->source }}</td>
                        <td>{{ Str::limit($log->message, 80) }}</td>
                        <td style="color: var(--neon-blue); font-family: monospace;">{{ $log->ip_address ?? 'N/A' }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="5" style="text-align: center; padding: 30px;">Không có dữ liệu Log.</td></tr>
                    @endforelse
                </tbody>
            </table>
            
            <div style="margin-top: 20px;">
                {{ $logs->links('pagination::bootstrap-4') }}
            </div>
        </div>
    </main>

    <script>
        const ctx = document.getElementById('attackChart').getContext('2d');
        const attackChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: {!! json_encode($chartLabels) !!},
                datasets: [
                    {
                        label: 'Tấn công (Attack)',
                        data: {!! json_encode($chartAttack) !!},
                        borderColor: '#ef4444',
                        backgroundColor: 'rgba(239, 68, 68, 0.1)',
                        borderWidth: 2,
                        fill: true,
                        tension: 0.4
                    },
                    {
                        label: 'Cảnh báo (Alert)',
                        data: {!! json_encode($chartAlert) !!},
                        borderColor: '#eab308',
                        backgroundColor: 'rgba(234, 179, 8, 0.1)',
                        borderWidth: 2,
                        fill: true,
                        tension: 0.4
                    }
                ]
            },
            options: {
                responsive: true,
                plugins: { legend: { labels: { color: '#9ca3af' } } },
                scales: {
                    x: { ticks: { color: '#9ca3af' }, grid: { color: '#1f2937' } },
                    y: { ticks: { color: '#9ca3af', stepSize: 1 }, grid: { color: '#1f2937' }, beginAtZero: true }
                }
            }
        });
    </script>
</body>
</html>