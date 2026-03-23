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
        
        .analytics-grid { display: grid; grid-template-columns: 2fr 1fr; gap: 25px; margin-bottom: 25px; }
        @media (max-width: 1200px) { .analytics-grid { grid-template-columns: 1fr; } }

        /* --- BỘ LỌC CÓ HIỆU ỨNG NỔI --- */
        .filter-bar { display: flex; flex-wrap: wrap; gap: 15px; margin-bottom: 20px; background: rgba(255,255,255,0.02); padding: 15px; border-radius: 8px; border: 1px solid #1f2937; align-items: center;}
        .filter-input { background: #0b1120; border: 1px solid #374151; color: white; padding: 10px 15px; border-radius: 6px; outline: none; transition: all 0.3s ease; }
        .filter-input:hover { border-color: var(--neon-blue); box-shadow: 0 5px 15px rgba(59, 130, 246, 0.25); transform: translateY(-2px); cursor: pointer; }
        .filter-input:focus { border-color: var(--neon-blue); box-shadow: 0 0 10px var(--neon-blue); transform: translateY(-2px); }

        .btn-action { background: var(--neon-blue); color: white; border: none; padding: 10px 20px; border-radius: 6px; cursor: pointer; font-weight: bold; transition: all 0.3s ease; text-decoration: none;}
        .btn-action:hover { box-shadow: 0 5px 15px rgba(59, 130, 246, 0.4); transform: translateY(-2px); }
        .btn-clear { background: #374151; }
        .btn-clear:hover { box-shadow: 0 5px 15px rgba(156, 163, 175, 0.3); }

        input[type="date"] { position: relative; padding-right: 15px; }
        input[type="date"]::-webkit-calendar-picker-indicator { filter: invert(1); transform: scale(1.4); cursor: pointer; opacity: 0.6; transition: 0.2s; margin-left: auto; }
        input[type="date"]::-webkit-calendar-picker-indicator:hover { opacity: 1; transform: scale(1.6); }

        .custom-scrollbar::-webkit-scrollbar { width: 6px; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: #374151; border-radius: 10px; }
        .custom-scrollbar::-webkit-scrollbar-track { background: transparent; }

        .log-table { width: 100%; border-collapse: collapse; }
        .log-table th { text-align: left; color: #9ca3af; padding: 15px; border-bottom: 1px solid #374151; font-size: 13px; }
        .log-table td { padding: 15px; border-bottom: 1px solid rgba(255,255,255,0.03); font-size: 14px; }
        .log-table tbody tr { transition: all 0.3s ease; }
        .log-table tbody tr:hover { background-color: rgba(59, 130, 246, 0.08); box-shadow: inset 4px 0 0 var(--neon-blue); }
        .top-ip-table tbody tr:hover { background-color: rgba(239, 68, 68, 0.1); box-shadow: inset 4px 0 0 var(--neon-red); }

        .badge { padding: 4px 10px; border-radius: 4px; font-size: 12px; font-weight: bold; text-transform: uppercase; }
        .badge-danger { color: var(--neon-red); border: 1px solid var(--neon-red); } 
        .badge-warning { color: var(--neon-yellow); border: 1px solid var(--neon-yellow); } 
        .badge-info { color: var(--neon-blue); border: 1px solid var(--neon-blue); } 

        /* --- CSS MỚI: BỘ ĐIỀU HƯỚNG NGÀY CHUYÊN NGHIỆP --- */
        .day-navigator { display: flex; justify-content: center; align-items: center; gap: 20px; margin-top: 25px; padding: 15px; background: rgba(255, 255, 255, 0.02); border-radius: 8px; border: 1px solid var(--border-color); }
        .page-btn { background: #1f2937; color: var(--text-main); padding: 10px 20px; border-radius: 6px; text-decoration: none; font-weight: bold; transition: all 0.3s ease; display: flex; align-items: center; gap: 10px; border: 1px solid #374151; }
        .page-btn:hover:not(.disabled) { background: var(--neon-blue); border-color: var(--neon-blue); box-shadow: 0 0 15px rgba(59, 130, 246, 0.4); transform: translateY(-2px); }
        .page-btn.disabled { opacity: 0.5; cursor: not-allowed; background: #0b1120; border-color: #1f2937; color: #6b7280; }
        .current-day-badge { text-align: center; color: var(--neon-blue); font-size: 16px; padding: 0 20px; }
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
        <h1><span>📊 Phân Tích Sự Kiện (Analytics)</span></h1>
        
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
                            <tr><td colspan="2" style="text-align: center; color: var(--text-muted);">Chưa có dữ liệu.</td></tr>
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

                <input type="date" name="view_date" class="filter-input" value="{{ $viewDate }}" title="Chọn ngày xem Log" onchange="this.form.submit()">

                <button type="submit" class="btn-action"><i class="fas fa-search"></i> LỌC DỮ LIỆU</button>
                <a href="{{ route('logs.index') }}" class="btn-action btn-clear" title="Xóa bộ lọc về Hôm nay"><i class="fas fa-redo"></i> LÀM MỚI</a>
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
                        <td style="color: var(--text-muted); font-size: 13px;">{{ $log->created_at->format('H:i:s') }}</td>
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
                    <tr><td colspan="5" style="text-align: center; padding: 40px; color: var(--text-muted);"><i class="fas fa-folder-open" style="font-size: 30px; display:block; margin-bottom: 10px;"></i>Hệ thống an toàn. Không có sự kiện nào trong ngày này.</td></tr>
                    @endforelse
                </tbody>
            </table>
            
            <div class="day-navigator">
                <a href="{{ request()->fullUrlWithQuery(['view_date' => $prevDate]) }}" class="page-btn">
                    <i class="fas fa-chevron-left"></i> Tua về: {{ \Carbon\Carbon::parse($prevDate)->format('d/m') }}
                </a>

                <div class="current-day-badge">
                    <i class="fas fa-calendar-alt"></i> Ngày đang xem: <strong style="color: white; font-size: 18px;">{{ \Carbon\Carbon::parse($viewDate)->format('d/m/Y') }}</strong>
                    <span style="display: block; font-size: 13px; color: var(--text-muted); margin-top: 5px;">Ghi nhận: {{ $logs->count() }} sự kiện</span>
                </div>

                @if(!$isToday)
                <a href="{{ request()->fullUrlWithQuery(['view_date' => $nextDate]) }}" class="page-btn">
                    Tới ngày: {{ \Carbon\Carbon::parse($nextDate)->format('d/m') }} <i class="fas fa-chevron-right"></i>
                </a>
                @else
                <span class="page-btn disabled" title="Không thể xem tương lai">
                    Chưa có ngày mai <i class="fas fa-ban"></i>
                </span>
                @endif
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