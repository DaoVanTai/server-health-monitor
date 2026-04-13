<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Unified Security Audit - Aegis OS</title>
    
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Roboto+Mono:wght@400;500&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --bg-main: #000000; --bg-card: #0a0a0a; --border-color: #27272a; --border-hover: #3f3f46;
            --text-main: #fafafa; --text-muted: #a1a1aa;
            --neon-purple: #c084fc; --neon-blue: #38bdf8; --neon-green: #34d399;
            --neon-red: #f87171; --neon-orange: #fbbf24; --neon-cyan: #22d3ee;
        }

        * { box-sizing: border-box; }
        body { margin: 0; padding: 0; background-color: var(--bg-main); color: var(--text-main); font-family: 'Inter', sans-serif; display: flex; min-height: 100vh; }
        
        .sidebar { width: 72px; background-color: var(--bg-main); border-right: 1px solid var(--border-color); display: flex; flex-direction: column; padding: 20px 0; transition: width 0.2s ease; overflow: hidden; white-space: nowrap; position: fixed; height: 100vh; z-index: 1000; }
        .sidebar:hover { width: 240px; background-color: var(--bg-card); }
        .sidebar-logo-container { width: 100%; display: flex; align-items: center; padding: 0 16px; margin-bottom: 24px; padding-bottom: 24px; border-bottom: 1px solid var(--border-color); }
        .sidebar-logo { min-width: 40px; height: 40px; border-radius: 10px; background: linear-gradient(135deg, var(--neon-cyan), var(--neon-purple)); display: flex; justify-content: center; align-items: center; font-size: 20px; color: white; flex-shrink: 0; }
        .sidebar-logo-text { margin-left: 12px; opacity: 0; font-weight: 800; font-size: 16px; background: linear-gradient(135deg, #fff, #a1a1aa); -webkit-background-clip: text; -webkit-text-fill-color: transparent; transition: opacity 0.2s;}
        .sidebar:hover .sidebar-logo-text { opacity: 1; }
        .sidebar-item { width: 100%; padding: 16px 0; display: flex; align-items: center; color: var(--text-muted); text-decoration: none; transition: all 0.2s; border-right: 2px solid transparent; }
        .sidebar-icon-wrapper { min-width: 72px; display: flex; justify-content: center; align-items: center; font-size: 1.1rem;}
        .sidebar-item span { opacity: 0; transition: opacity 0.2s; font-size: 14px; font-weight: 500;}
        .sidebar:hover .sidebar-item span { opacity: 1; }
        .sidebar-item.active { color: var(--text-main); background: rgba(255,255,255,0.03); }
        .sidebar-item:hover:not(.active) { color: var(--text-main); background: rgba(255,255,255,0.05); }

        .main-content { flex: 1; margin-left: 72px; padding: 40px 48px; display: flex; flex-direction: column; gap: 24px; max-width: 1600px; margin-right: auto;}
        .header { display: flex; justify-content: space-between; align-items: center; padding-bottom: 8px;}
        .title-area h1 { font-size: 24px; font-weight: 700; margin: 0; color: var(--text-main); display: flex; align-items: center; gap: 12px;}
        .title-area p { color: var(--text-muted); margin: 4px 0 0 0; font-size: 14px;}
        .btn-logout { background: transparent; border: 1px solid var(--border-color); color: var(--text-main); padding: 8px 16px; border-radius: 6px; cursor: pointer; font-size: 13px; display: flex; align-items: center; gap: 8px;}
        .btn-logout:hover { border-color: var(--border-hover); background: rgba(255,255,255,0.05); }
        .badge-secure { background: rgba(56, 189, 248, 0.1); border: 1px solid rgba(56, 189, 248, 0.2); color: var(--neon-blue); padding: 6px 12px; border-radius: 9999px; font-weight: 500; font-size: 12px; display: flex; align-items: center; gap: 8px;}

        .panel { background-color: var(--bg-card); border: 1px solid var(--border-color); border-radius: 12px; padding: 24px; display: flex; flex-direction: column; }
        .panel-header { display: flex; align-items: center; font-size: 14px; font-weight: 600; margin-bottom: 20px; color: var(--text-main);}
        .grid-2col { display: grid; grid-template-columns: 1fr 1fr; gap: 24px; }
        
        .filter-bar { display: flex; flex-wrap: wrap; gap: 12px; margin-bottom: 20px; background: rgba(255,255,255,0.02); padding: 16px; border-radius: 8px; border: 1px solid var(--border-color); align-items: center; }
        .filter-input { background: #000000; border: 1px solid var(--border-color); color: var(--text-main); padding: 10px 14px; border-radius: 6px; outline: none; font-size: 13px; color-scheme: dark; }
        .btn-action { background: rgba(56, 189, 248, 0.1); color: var(--neon-blue); border: 1px solid rgba(56, 189, 248, 0.2); padding: 10px 20px; border-radius: 6px; cursor: pointer; font-weight: 600; text-decoration: none; font-size: 13px; display: flex; align-items: center; gap: 8px; }
        .btn-clear { background: transparent; border: 1px solid var(--border-color); color: var(--text-muted); }

        .log-table { width: 100%; border-collapse: separate; border-spacing: 0; font-size: 13px; text-align: left; }
        .log-table th { padding: 12px 12px; color: var(--text-muted); font-weight: 500; border-bottom: 1px solid var(--border-color); font-size: 12px; text-transform: uppercase;}
        .log-table td { padding: 14px 12px; border-bottom: 1px solid #18181b; color: var(--text-main); font-weight: 400;}
        
        .badge { padding: 4px 8px; border-radius: 4px; font-size: 11px; font-weight: 600; display: inline-flex; align-items: center; gap: 4px;}
        .badge-danger { background: rgba(248, 113, 113, 0.1); color: var(--neon-red); border: 1px solid rgba(248, 113, 113, 0.2); } 
        .badge-success { background: rgba(52, 211, 153, 0.1); color: var(--neon-green); border: 1px solid rgba(52, 211, 153, 0.2); } 
        .badge-warning { background: rgba(251, 191, 36, 0.1); color: var(--neon-orange); border: 1px solid rgba(251, 191, 36, 0.2); } 
        
        .custom-scrollbar::-webkit-scrollbar { width: 4px; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: #3f3f46; border-radius: 4px; }
    </style>
</head>
<body>

    <aside class="sidebar">
        <div class="sidebar-logo-container">
            <div class="sidebar-logo"><i class="fas fa-shield-virus"></i></div>
            <div class="sidebar-logo-text">AEGIS OS</div>
        </div>

        <a href="{{ route('monitor') }}" class="sidebar-item {{ Request::is('monitor*') ? 'active' : '' }}">
            <div class="sidebar-icon-wrapper"><i class="fas fa-layer-group"></i></div><span>Overview</span>
        </a>
        <a href="{{ route('network.index') }}" class="sidebar-item {{ Request::is('network*') ? 'active' : '' }}">
            <div class="sidebar-icon-wrapper"><i class="fas fa-globe"></i></div><span>Network</span>
        </a>
        <a href="{{ route('firewall.index') }}" class="sidebar-item {{ Request::is('firewall*') ? 'active' : '' }}">
            <div class="sidebar-icon-wrapper"><i class="fas fa-shield-alt"></i></div><span>Security</span>
        </a>
        <a href="{{ route('ai.index') }}" class="sidebar-item {{ Request::is('ai-intelligence*') ? 'active' : '' }}">
            <div class="sidebar-icon-wrapper"><i class="fas fa-sparkles"></i></div><span>AI Insight</span>
        </a>
        <a href="{{ route('logs.index') }}" class="sidebar-item active">
            <div class="sidebar-icon-wrapper"><i class="fas fa-clipboard-check"></i></div><span>Security Audit</span>
        </a>
    </aside>

    <main class="main-content">
        <header class="header">
            <div class="title-area">
                <h1><i class="fas fa-clipboard-check" style="color: var(--neon-blue);"></i> Unified Security Audit</h1>
                <p>Hợp nhất Nhật ký Tường lửa và SSH Radar</p>
            </div>
            <div style="display: flex; gap: 16px; align-items: center;">
                <div class="badge-secure"><i class="fas fa-database"></i> Database Synced</div>
                <form action="{{ route('logout') }}" method="POST" style="margin: 0;">
                    @csrf
                    <button type="submit" class="btn-logout"><i class="fas fa-sign-out-alt"></i> Sign Out</button>
                </form>
            </div>
        </header>

        <div class="panel">
            <div class="panel-header">
                <span><i class="fas fa-chart-line" style="color: var(--text-muted); margin-right: 8px;"></i> Events Overview (Last 7 Days)</span>
            </div>
            <div style="height: 250px; width: 100%;"><canvas id="logChart"></canvas></div>
        </div>

        <div class="grid-2col">
            <div class="panel" style="padding: 16px 24px;">
                <div class="panel-header" style="margin-bottom: 10px;">
                    <span><i class="fas fa-crosshairs" style="color: var(--neon-red); margin-right: 8px;"></i> Top SSH Attackers</span>
                </div>
                <div class="custom-scrollbar" style="max-height: 200px; overflow-y: auto;">
                    <table class="log-table">
                        <tbody>
                            @forelse($sshData['topAttackers'] as $ip => $data)
                            <tr>
                                <td style="color: var(--neon-red); font-family: 'Roboto Mono', monospace; font-weight: 600;">{{ $ip }}</td>
                                <td style="text-align: right;"><span class="badge badge-warning">{{ $data['count'] }} attempts</span></td>
                            </tr>
                            @empty
                            <tr><td colspan="2" style="text-align: center; color: var(--text-muted);">Không có rà quét SSH.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="panel" style="padding: 16px 24px;">
                <div class="panel-header" style="margin-bottom: 10px;">
                    <span><i class="fas fa-check-circle" style="color: var(--neon-green); margin-right: 8px;"></i> Recent SSH Logins</span>
                </div>
                <div class="custom-scrollbar" style="max-height: 200px; overflow-y: auto;">
                    <table class="log-table">
                        <tbody>
                            @forelse($sshData['recentLogins'] as $login)
                            <tr>
                                <td style="color: var(--text-muted); font-size: 12px;">{{ $login['time'] }}</td>
                                <td style="color: var(--text-main); font-weight: 500;">{{ $login['user'] }}</td>
                                <td style="color: var(--neon-blue); font-family: 'Roboto Mono', monospace;">{{ $login['ip'] }}</td>
                            </tr>
                            @empty
                            <tr><td colspan="3" style="text-align: center; color: var(--text-muted);">Chưa có lượt đăng nhập.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="panel">
            <div class="panel-header">
                <span><i class="fas fa-filter" style="color: var(--text-muted); margin-right: 8px;"></i> Detailed Firewall Logs Explorer</span>
            </div>
            
            <form action="{{ route('logs.index') }}" method="GET" class="filter-bar">
                <input type="text" name="search" class="filter-input" placeholder="Tìm theo IP, Nội dung..." value="{{ request('search') }}" style="flex:1; min-width: 200px;">
                
                <select name="level" class="filter-input">
                    <option value="all" {{ request('level') == 'all' ? 'selected' : '' }}>Tất cả sự kiện</option>
                    <option value="danger" {{ request('level') == 'danger' ? 'selected' : '' }}>🔴 IP Bị Chặn (Blocked)</option>
                    <option value="success" {{ request('level') == 'success' ? 'selected' : '' }}>🟢 IP Được Gỡ (Unblocked)</option>
                    <option value="warning" {{ request('level') == 'warning' ? 'selected' : '' }}>🟡 Cảnh báo (Alert)</option>
                </select>

                <input type="date" name="from_date" class="filter-input" value="{{ $fromDate }}">
                <span style="color: var(--text-muted);">-</span>
                <input type="date" name="to_date" class="filter-input" value="{{ $toDate }}">

                <button type="submit" class="btn-action"><i class="fas fa-search"></i> Lọc dữ liệu</button>
                <a href="{{ route('logs.index') }}" class="btn-action btn-clear" title="Xóa bộ lọc"><i class="fas fa-redo"></i></a>
            </form>

            <div class="custom-scrollbar" style="max-height: 500px; overflow-y: auto;">
                <table class="log-table">
                    <thead style="position: sticky; top: 0; background: var(--bg-card); z-index: 10;">
                        <tr>
                            <th>Thời gian</th>
                            <th>Phân loại</th>
                            <th>Nội dung chi tiết</th>
                            <th>Target IP</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($dbLogs as $log)
                        <tr>
                            <td style="color: var(--text-muted); font-size: 12px; white-space: nowrap;">{{ $log->created_at->format('H:i:s d/m') }}</td>
                            <td>
                                @if($log->level == 'danger') <span class="badge badge-danger"><i class="fas fa-lock"></i> blocked</span>
                                @elseif($log->level == 'success') <span class="badge badge-success"><i class="fas fa-unlock"></i> unblocked</span>
                                @elseif($log->level == 'warning') <span class="badge badge-warning"><i class="fas fa-exclamation-triangle"></i> alert</span>
                                @endif
                            </td>
                            <td>
                                <strong style="color: var(--text-muted); font-size: 12px;">[{{ $log->source }}]</strong> <br>
                                <span style="font-size: 13px;">{{ Str::limit($log->message, 100) }}</span>
                            </td>
                            <td style="color: var(--text-main); font-family: 'Roboto Mono', monospace; font-size: 13px;">{{ $log->ip_address ?? 'N/A' }}</td>
                        </tr>
                        @empty
                        <tr><td colspan="4" style="text-align: center; padding: 40px; color: var(--text-muted);">Không tìm thấy sự kiện nào khớp với bộ lọc.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <script>
        Chart.defaults.color = '#a1a1aa';
        Chart.defaults.font.family = 'Inter, sans-serif';
        Chart.defaults.plugins.tooltip.backgroundColor = '#18181b';
        Chart.defaults.plugins.tooltip.titleColor = '#fafafa';
        Chart.defaults.plugins.tooltip.bodyColor = '#a1a1aa';
        Chart.defaults.plugins.tooltip.borderColor = '#27272a';
        Chart.defaults.plugins.tooltip.borderWidth = 1;
        Chart.defaults.plugins.tooltip.padding = 10;
        Chart.defaults.plugins.tooltip.cornerRadius = 6;

        const ctx = document.getElementById('logChart').getContext('2d');
        const logChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: {!! json_encode($chartLabels) !!},
                datasets: [
                    {
                        label: 'IP Bị Chặn (Blocked)',
                        data: {!! json_encode($chartBlocked) !!},
                        borderColor: '#f87171', 
                        backgroundColor: 'rgba(248, 113, 113, 0.1)',
                        borderWidth: 2, fill: true, tension: 0.4, pointRadius: 0, pointHoverRadius: 4
                    },
                    {
                        label: 'IP Gỡ Chặn (Unblocked)',
                        data: {!! json_encode($chartUnblocked) !!},
                        borderColor: '#34d399', 
                        backgroundColor: 'rgba(52, 211, 153, 0.1)',
                        borderWidth: 2, fill: true, tension: 0.4, pointRadius: 0, pointHoverRadius: 4
                    },
                    {
                        label: 'Cảnh Báo (Alert)',
                        data: {!! json_encode($chartAlert) !!},
                        borderColor: '#fbbf24', 
                        backgroundColor: 'rgba(251, 191, 36, 0.1)',
                        borderWidth: 2, fill: true, tension: 0.4, pointRadius: 0, pointHoverRadius: 4
                    }
                ]
            },
            options: {
                responsive: true, maintainAspectRatio: false, interaction: { mode: 'index', intersect: false },
                plugins: { legend: { labels: { color: '#a1a1aa', usePointStyle: true, boxWidth: 6 } } },
                scales: {
                    x: { border: {display: false}, grid: { display: false } },
                    y: { border: {display: false}, grid: { color: '#18181b', borderDash: [5,5] }, beginAtZero: true, ticks: { stepSize: 1 } }
                }
            }
        });
    </script>
</body>
</html>