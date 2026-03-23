<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Log & Analytics - Security Center</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        :root {
            --bg-main: #0b1120; --bg-card: #111827; 
            --neon-red: #ef4444;       /* Tấn công */
            --neon-green: #10b981;     /* Gỡ chặn */
            --neon-yellow: #eab308;    /* Cảnh báo Alert */
            --neon-blue: #3b82f6;      /* Hệ thống System */
            --text-main: #f3f4f6; --text-muted: #9ca3af; --border-color: #1f2937;
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
        .analytics-grid { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 20px; margin-bottom: 25px; }
        .grid-2col { display: grid; grid-template-columns: 2fr 1fr; gap: 20px; margin-bottom: 25px; }
        @media (max-width: 1200px) { .analytics-grid, .grid-2col { grid-template-columns: 1fr; } }

        .filter-bar { display: flex; flex-wrap: wrap; gap: 15px; margin-bottom: 20px; background: rgba(255,255,255,0.02); padding: 15px; border-radius: 8px; border: 1px solid #1f2937; align-items: center;}
        .filter-input { background: #0b1120; border: 1px solid #374151; color: white; padding: 10px 15px; border-radius: 6px; outline: none; transition: all 0.3s ease; }
        .filter-input:hover, .filter-input:focus { border-color: var(--neon-blue); box-shadow: 0 0 10px rgba(59, 130, 246, 0.25); transform: translateY(-2px); }

        .btn-action { background: var(--neon-blue); color: white; border: none; padding: 10px 20px; border-radius: 6px; cursor: pointer; font-weight: bold; transition: all 0.3s ease; text-decoration: none;}
        .btn-action:hover { box-shadow: 0 5px 15px rgba(59, 130, 246, 0.4); transform: translateY(-2px); }
        .btn-clear { background: #374151; }

        input[type="date"]::-webkit-calendar-picker-indicator { filter: invert(1); transform: scale(1.4); cursor: pointer; opacity: 0.6; }

        .custom-scrollbar::-webkit-scrollbar { width: 6px; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: #374151; border-radius: 10px; }
        .custom-scrollbar::-webkit-scrollbar-track { background: transparent; }

        .log-table { width: 100%; border-collapse: collapse; }
        .log-table th { text-align: left; color: #9ca3af; padding: 15px; border-bottom: 1px solid #374151; font-size: 13px; text-transform: uppercase;}
        .log-table td { padding: 15px; border-bottom: 1px solid rgba(255,255,255,0.03); font-size: 14px; }
        .log-table tbody tr:hover { background-color: rgba(59, 130, 246, 0.08); }

        .badge { padding: 4px 10px; border-radius: 4px; font-size: 12px; font-weight: bold; }
        .badge-danger { color: var(--neon-red); } 
        .badge-success { color: var(--neon-green); } 
        .badge-warning { color: var(--neon-yellow); } 
        .badge-info { color: var(--neon-blue); } 

        .summary-footer { margin-top: 25px; padding: 20px; background: rgba(59, 130, 246, 0.05); border-radius: 8px; border: 1px dashed var(--neon-blue); display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px; }
        .stat-box { background: var(--bg-main); padding: 8px 15px; border-radius: 6px; border: 1px solid var(--border-color); font-size: 14px; display: flex; align-items: center; gap: 8px; }
        
        .terminal-box { background: #000; padding: 20px; border-radius: 8px; font-family: 'Courier New', Courier, monospace; font-size: 13px; line-height: 1.6; max-height: 250px; overflow-y: auto; box-shadow: inset 0 0 10px rgba(0,0,0,0.8); }
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
        <h1><span>📊 LOG & ANALYTICS (PHÂN TÍCH NHẬT KÝ HỆ THỐNG)</span></h1>

        <div class="grid-2col">
            <div class="card">
                <h3 style="margin-top:0; color:var(--text-muted);"><i class="fas fa-chart-line"></i> Biểu Đồ Log Theo Thời Gian</h3>
                <canvas id="attackChart" height="90"></canvas>
            </div>

            <div class="card" style="display: flex; flex-direction: column; padding: 15px;">
                <h3 style="margin-top:0; color:var(--neon-red);"><i class="fas fa-stopwatch"></i> Attack Timeline</h3>
                <div class="terminal-box custom-scrollbar" style="flex: 1;">
                    <div style="color: #6b7280; margin-bottom: 10px;">// Theo dõi sự kiện theo trình tự thời gian</div>
                    @forelse($attackTimeline as $timeline)
                        <div>
                            <span style="color: #fff;">{{ $timeline->created_at->format('H:i') }}</span> - 
                            @if($timeline->level == 'danger')
                                <span style="color: var(--neon-red);">Attack detected ({{ $timeline->ip_address }})</span>
                            @elseif($timeline->level == 'success')
                                <span style="color: var(--neon-green);">Unblock IP: {{ $timeline->ip_address }}</span>
                            @elseif($timeline->level == 'warning')
                                <span style="color: var(--neon-yellow);">Alert: {{ Str::limit($timeline->message, 40) }}</span>
                            @endif
                        </div>
                    @empty
                        <div style="color: #10b981;">[OK] Không phát hiện tấn công nào.</div>
                    @endforelse
                </div>
            </div>
        </div>

        <div class="analytics-grid">
            <div class="card" style="grid-column: span 2;">
                <h3 style="margin-top:0; color:var(--neon-yellow);"><i class="fas fa-exclamation-triangle"></i> Thống Kê Số Lần Cảnh Báo</h3>
                <div class="custom-scrollbar" style="max-height: 200px; overflow-y: auto;">
                    <table class="log-table">
                        <thead style="position: sticky; top: 0; background: var(--bg-card);">
                            <tr><th>Type (Loại cảnh báo)</th><th>Count</th></tr>
                        </thead>
                        <tbody>
                            @forelse($alertDetails as $alert)
                            <tr>
                                <td style="color: #cbd5e1;">{{ $alert->source }}</td>
                                <td style="font-weight:bold; color:var(--neon-yellow);">{{ $alert->total }}</td>
                            </tr>
                            @empty
                            <tr><td colspan="2" style="text-align:center; color: var(--text-muted);">Không có cảnh báo nào.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="card">
                <h3 style="margin-top:0; color:var(--neon-red);"><i class="fas fa-skull-crossbones"></i> Top IP Tấn Công</h3>
                <div class="custom-scrollbar" style="max-height: 200px; overflow-y: auto;">
                    <table class="log-table">
                        <thead style="position: sticky; top: 0; background: var(--bg-card);">
                            <tr><th>IP Address</th><th>Attempts</th></tr>
                        </thead>
                        <tbody>
                            @forelse($topIps as $top)
                            <tr>
                                <td style="color: var(--neon-red); font-family: monospace;">{{ $top->ip_address }}</td>
                                <td style="font-weight:bold;">{{ $top->total }}</td>
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
                    <option value="danger" {{ request('level') == 'danger' ? 'selected' : '' }}>🔴 Tấn công / 🟢 Gỡ chặn (Security)</option>
                    <option value="warning" {{ request('level') == 'warning' ? 'selected' : '' }}>🟡 Cảnh báo (Alert)</option>
                    <option value="info" {{ request('level') == 'info' ? 'selected' : '' }}>🔵 Hệ thống (System)</option>
                </select>

                <input type="date" name="from_date" class="filter-input" value="{{ $fromDate }}">
                <span style="color: var(--text-muted);">-</span>
                <input type="date" name="to_date" class="filter-input" value="{{ $toDate }}">

                <button type="submit" class="btn-action"><i class="fas fa-search"></i> LỌC DỮ LIỆU</button>
                <a href="{{ route('logs.index') }}" class="btn-action btn-clear" title="Xóa bộ lọc"><i class="fas fa-redo"></i></a>
            </form>

            <div class="custom-scrollbar" style="max-height: 500px; overflow-y: auto;">
                <table class="log-table">
                    <thead>
                        <tr>
                            <th style="position: sticky; top: 0; background: var(--bg-card);">Time</th>
                            <th style="position: sticky; top: 0; background: var(--bg-card);">Type</th>
                            <th style="position: sticky; top: 0; background: var(--bg-card);">Message</th>
                            <th style="position: sticky; top: 0; background: var(--bg-card);">IP</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($logs as $log)
                        <tr>
                            <td style="color: var(--text-muted); font-size: 13px;">{{ $log->created_at->format('H:i:s d/m') }}</td>
                            <td>
                                @if($log->level == 'danger') <span class="badge badge-danger">🔴 attack</span>
                                @elseif($log->level == 'success') <span class="badge badge-success">🟢 unblock</span>
                                @elseif($log->level == 'warning') <span class="badge badge-warning">🟡 alert</span>
                                @elseif($log->level == 'info') <span class="badge badge-info">🔵 system</span>
                                @endif
                            </td>
                            <td>
                                <strong style="color: #9ca3af;">[{{ $log->source }}]</strong> 
                                {{ Str::limit($log->message, 80) }}
                            </td>
                            <td style="color: var(--neon-blue); font-family: monospace;">{{ $log->ip_address ?? 'null' }}</td>
                        </tr>
                        @empty
                        <tr><td colspan="4" style="text-align: center; padding: 40px; color: var(--text-muted);">Không tìm thấy sự kiện nào.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            
            @php
                $displayFrom = $fromDate ? \Carbon\Carbon::parse($fromDate)->format('d/m/Y') : 'Khởi tạo hệ thống';
                $displayTo = $toDate ? \Carbon\Carbon::parse($toDate)->format('d/m/Y') : 'Hiện tại';
                // Đếm chính xác theo đúng Database Map
                $countDanger = \App\Models\SystemLog::whereIn('level', ['danger', 'success'])->count();
                $countWarning = \App\Models\SystemLog::where('level', 'warning')->count();
                $countInfo = \App\Models\SystemLog::where('level', 'info')->count();
            @endphp
            
            <div class="summary-footer">
                <div style="font-size: 16px; color: white;">
                    <i class="fas fa-calendar-alt" style="color: var(--neon-blue); margin-right: 5px;"></i>
                    Đang xem từ <strong>{{ $displayFrom }}</strong> đến <strong>{{ $displayTo }}</strong>
                </div>
                
                <div style="display: flex; gap: 15px; align-items: center;">
                    <div class="stat-box" style="border-color: var(--neon-blue);">
                        <span style="color: var(--text-muted);">Tổng sự kiện:</span>
                        <strong style="color: white; font-size: 16px;">{{ $logs->count() }}</strong>
                    </div>
                    <div class="stat-box" style="border-left: 3px solid var(--neon-red);">
                        <strong style="color: var(--neon-red);">{{ $countDanger }}</strong> <span style="font-size: 12px; color: #9ca3af;">Security</span>
                    </div>
                    <div class="stat-box" style="border-left: 3px solid var(--neon-yellow);">
                        <strong style="color: var(--neon-yellow);">{{ $countWarning }}</strong> <span style="font-size: 12px; color: #9ca3af;">Alert</span>
                    </div>
                    <div class="stat-box" style="border-left: 3px solid var(--neon-blue);">
                        <strong style="color: var(--neon-blue);">{{ $countInfo }}</strong> <span style="font-size: 12px; color: #9ca3af;">System</span>
                    </div>
                </div>
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
                        label: 'Số attack/unblock (Security)',
                        data: {!! json_encode($chartAttack) !!},
                        borderColor: '#ef4444', // Vẫn giữ đường biên đỏ cho Tấn công
                        backgroundColor: 'rgba(239, 68, 68, 0.1)',
                        borderWidth: 2,
                        fill: true,
                        tension: 0.4
                    },
                    {
                        label: 'Số alert (Cảnh báo CPU/RAM)',
                        data: {!! json_encode($chartAlert) !!},
                        borderColor: '#eab308', // Màu vàng
                        backgroundColor: 'rgba(234, 179, 8, 0.1)',
                        borderWidth: 2,
                        fill: true,
                        tension: 0.4
                    },
                    {
                        label: 'Số system (Log hệ thống)',
                        data: {!! json_encode($chartSystem) !!},
                        borderColor: '#3b82f6', // Màu xanh dương
                        backgroundColor: 'rgba(59, 130, 246, 0.1)',
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