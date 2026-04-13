<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Audit Logs Explorer - Security Center</title>
    
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Roboto+Mono:wght@400;500&display=swap" rel="stylesheet">
    
    <style>
        :root {
            /* Vercel/Linear Deep Dark Theme Palette */
            --bg-main: #000000; --bg-card: #0a0a0a; --border-color: #27272a; --border-hover: #3f3f46;
            --text-main: #fafafa; --text-muted: #a1a1aa;
            --neon-purple: #c084fc; --neon-blue: #38bdf8; --neon-green: #34d399;
            --neon-red: #f87171; --neon-orange: #fbbf24; --neon-cyan: #22d3ee;
            --shadow-soft: 0 10px 30px rgba(0, 0, 0, 0.5);
        }

        * { box-sizing: border-box; }
        body { margin: 0; padding: 0; background-color: var(--bg-main); color: var(--text-main); font-family: 'Inter', -apple-system, sans-serif; display: flex; min-height: 100vh; -webkit-font-smoothing: antialiased;}
        
        /* --- SIDEBAR ĐỒNG BỘ --- */
        .sidebar { width: 72px; background-color: var(--bg-main); border-right: 1px solid var(--border-color); display: flex; flex-direction: column; padding: 20px 0; transition: width 0.2s ease; overflow: hidden; white-space: nowrap; position: fixed; height: 100vh; z-index: 1000;}
        .sidebar:hover { width: 240px; background-color: var(--bg-card); }
        
        .sidebar-logo-container { width: 100%; display: flex; align-items: center; padding: 0 16px; margin-bottom: 24px; padding-bottom: 24px; border-bottom: 1px solid var(--border-color); cursor: pointer;}
        .sidebar-logo { min-width: 40px; height: 40px; border-radius: 10px; background: linear-gradient(135deg, var(--neon-cyan), var(--neon-purple)); display: flex; justify-content: center; align-items: center; font-size: 20px; color: white; flex-shrink: 0; }
        .sidebar-logo-text { margin-left: 12px; opacity: 0; transition: opacity 0.2s; font-weight: 800; font-size: 16px; letter-spacing: 0.5px; background: linear-gradient(135deg, #fff, #a1a1aa); -webkit-background-clip: text; -webkit-text-fill-color: transparent;}
        .sidebar:hover .sidebar-logo-text { opacity: 1; }

        .sidebar-item { width: 100%; padding: 16px 0; display: flex; align-items: center; color: var(--text-muted); text-decoration: none; transition: all 0.2s; border-right: 2px solid transparent; }
        .sidebar-icon-wrapper { min-width: 72px; display: flex; justify-content: center; align-items: center; font-size: 1.1rem;}
        .sidebar-item span { opacity: 0; transition: opacity 0.2s; font-size: 14px; font-weight: 500;}
        .sidebar:hover .sidebar-item span { opacity: 1; }
        .sidebar-item.active { color: var(--text-main); background: rgba(255,255,255,0.03); }
        .sidebar-item:hover:not(.active) { color: var(--text-main); background: rgba(255,255,255,0.05); }

        /* --- MAIN CONTENT & TASK BAR CHUẨN --- */
        .main-content { flex: 1; margin-left: 72px; padding: 40px 48px; display: flex; flex-direction: column; gap: 24px; max-width: 1600px; margin-right: auto;}
        
        .header { display: flex; justify-content: space-between; align-items: center; padding-bottom: 8px;}
        .title-area h1 { font-size: 24px; font-weight: 700; margin: 0; color: var(--text-main); letter-spacing: -0.5px; display: flex; align-items: center; gap: 12px;}
        .title-area p { color: var(--text-muted); margin: 4px 0 0 0; font-size: 14px; font-weight: 400;}
        
        .btn-logout { background: transparent; border: 1px solid var(--border-color); color: var(--text-main); border-radius: 6px; cursor: pointer; transition: all 0.2s; display: flex; align-items: center; justify-content: center;}
        .btn-logout:hover { border-color: var(--border-hover); background: rgba(255,255,255,0.05); }

        .badge-secure { background: rgba(56, 189, 248, 0.1); border: 1px solid rgba(56, 189, 248, 0.2); color: var(--neon-blue); padding: 6px 14px; border-radius: 9999px; font-weight: 500; font-size: 12px; display: flex; align-items: center; gap: 8px;}

        /* --- SECTIONS & PANELS --- */
        .panel { background-color: var(--bg-card); border: 1px solid var(--border-color); border-radius: 12px; padding: 24px; display: flex; flex-direction: column; }
        .panel-header { display: flex; justify-content: space-between; align-items: center; font-size: 14px; font-weight: 600; margin-bottom: 20px; color: var(--text-main);}

        /* --- FILTER BAR KHÔI PHỤC --- */
        .filter-bar { display: flex; flex-wrap: wrap; gap: 12px; margin-bottom: 20px; background: rgba(255,255,255,0.02); padding: 16px; border-radius: 8px; border: 1px solid var(--border-color); align-items: center; }
        .filter-input { background: #000000; border: 1px solid var(--border-color); color: var(--text-main); padding: 10px 14px; border-radius: 6px; outline: none; font-size: 13px; color-scheme: dark; transition: 0.2s;}
        .filter-input:focus { border-color: var(--neon-blue); box-shadow: 0 0 0 1px rgba(56, 189, 248, 0.2); }
        .btn-action { background: rgba(56, 189, 248, 0.1); color: var(--neon-blue); border: 1px solid rgba(56, 189, 248, 0.2); padding: 10px 20px; border-radius: 6px; cursor: pointer; font-weight: 600; text-decoration: none; font-size: 13px; display: flex; align-items: center; gap: 8px; transition: 0.2s;}
        .btn-action:hover { background: rgba(56, 189, 248, 0.2); }
        .btn-clear { background: transparent; border: 1px solid var(--border-color); color: var(--text-muted); }
        .btn-clear:hover { color: var(--text-main); border-color: var(--border-hover); }

        /* --- TABLE --- */
        .log-table { width: 100%; border-collapse: separate; border-spacing: 0; font-size: 13px; text-align: left; }
        .log-table th { padding: 12px 12px; color: var(--text-muted); font-weight: 500; border-bottom: 1px solid var(--border-color); font-size: 12px; text-transform: uppercase;}
        .log-table td { padding: 14px 12px; border-bottom: 1px solid #18181b; color: var(--text-main); font-weight: 400;}
        .log-table tr:hover td { background-color: rgba(255,255,255,0.02); }
        
        .badge { padding: 4px 8px; border-radius: 4px; font-size: 11px; font-weight: 600; display: inline-flex; align-items: center; gap: 4px;}
        .badge-danger { background: rgba(248, 113, 113, 0.1); color: var(--neon-red); border: 1px solid rgba(248, 113, 113, 0.2); } 
        .badge-success { background: rgba(52, 211, 153, 0.1); color: var(--neon-green); border: 1px solid rgba(52, 211, 153, 0.2); } 
        .badge-warning { background: rgba(251, 191, 36, 0.1); color: var(--neon-orange); border: 1px solid rgba(251, 191, 36, 0.2); } 

        .scrollable-area::-webkit-scrollbar { width: 4px; }
        .scrollable-area::-webkit-scrollbar-thumb { background: #3f3f46; border-radius: 4px; }
        
        .dashboard-footer { text-align: right; font-size: 12px; color: var(--text-muted); margin-top: auto; padding-top: 20px;}
    </style>
</head>
<body>

    <aside class="sidebar">
        <div class="sidebar-logo-container">
            <div class="sidebar-logo"><i class="fas fa-shield-virus"></i></div>
            <div class="sidebar-logo-text">AEGIS OS</div>
        </div>

        <a href="{{ route('monitor') }}" class="sidebar-item">
            <div class="sidebar-icon-wrapper"><i class="fas fa-layer-group"></i></div><span>Overview</span>
        </a>
        <a href="{{ route('network.index') }}" class="sidebar-item">
            <div class="sidebar-icon-wrapper"><i class="fas fa-globe"></i></div><span>Network</span>
        </a>
        <a href="{{ route('firewall.index') }}" class="sidebar-item">
            <div class="sidebar-icon-wrapper"><i class="fas fa-shield-alt"></i></div><span>Security</span>
        </a>
        <a href="{{ route('ai.index') }}" class="sidebar-item">
            <div class="sidebar-icon-wrapper"><i class="fas fa-sparkles"></i></div><span>AI Insight</span>
        </a>
        <a href="{{ route('logs.index') }}" class="sidebar-item active">
            <div class="sidebar-icon-wrapper"><i class="fas fa-list-ul"></i></div><span>Security Audit</span>
        </a>
    </aside>

    <main class="main-content">
        <header class="header">
            <div class="title-area">
                <h1><i class="fas fa-chart-area" style="color: var(--neon-blue);"></i> Audit Logs Explorer</h1>
                <p>Advanced Search, Forensic Analytics & Historic Data</p>
            </div>
            
            <div style="display: flex; gap: 12px; align-items: center;">
                <div class="badge-secure"><i class="fas fa-database"></i> Database Synced</div>

                <div style="display: flex; align-items: center; background: rgba(255, 255, 255, 0.03); border: 1px solid var(--border-color); border-radius: 9999px; padding: 4px 4px 4px 14px; gap: 12px; margin-left: 8px;">
                    <div style="background: rgba(255, 255, 255, 0.1); padding: 4px 10px; border-radius: 9999px; font-size: 11px; font-weight: 700; color: var(--text-main); letter-spacing: 0.5px;">
                        {{ Auth::check() && Auth::user()->role ? Auth::user()->role : 'Admin' }}
                    </div>
                    <div style="font-size: 13px; font-weight: 600; color: var(--text-main);">
                        {{ Auth::check() ? Auth::user()->name : 'Tuan Anh' }}
                    </div>
                    @php
                        $name = Auth::check() ? Auth::user()->name : 'Tuan Anh';
                        $words = explode(' ', trim($name));
                        $initials = '';
                        if (count($words) >= 2) {
                            $initials = strtoupper(substr($words[0], 0, 1) . substr(end($words), 0, 1));
                        } else {
                            $initials = strtoupper(substr($name, 0, 2));
                        }
                    @endphp
                    <div style="width: 32px; height: 32px; border-radius: 50%; border: 1px solid var(--neon-blue); display: flex; justify-content: center; align-items: center; font-weight: 700; font-size: 12px; color: var(--neon-blue); box-shadow: 0 0 10px rgba(56, 189, 248, 0.2), inset 0 0 5px rgba(56, 189, 248, 0.1);">
                        {{ $initials }}
                    </div>
                </div>

                <form action="{{ route('logout') }}" method="POST" style="margin: 0; margin-left: 4px;">
                    @csrf
                    <button type="submit" class="btn-logout" title="Đăng xuất" style="padding: 8px 12px;">
                        <i class="fas fa-sign-out-alt"></i>
                    </button>
                </form>
            </div>
        </header>

        <div class="panel">
            <div class="panel-header">
                <span><i class="fas fa-chart-line" style="color: var(--text-muted); margin-right: 8px;"></i> Events Overview (Last 7 Days)</span>
            </div>
            <div style="height: 250px; width: 100%;"><canvas id="logChart"></canvas></div>
        </div>

        <div class="panel">
            <div class="panel-header">
                <span><i class="fas fa-filter" style="color: var(--text-muted); margin-right: 8px;"></i> Detailed Logs Explorer</span>
            </div>
            
            <form action="{{ route('logs.index') }}" method="GET" class="filter-bar">
                <input type="text" name="search" class="filter-input" placeholder="Tìm theo IP, Nội dung..." value="{{ request('search') }}" style="flex:1; min-width: 200px;">
                
                <select name="level" class="filter-input">
                    <option value="all" {{ request('level') == 'all' ? 'selected' : '' }}>Tất cả sự kiện</option>
                    <option value="danger" {{ request('level') == 'danger' ? 'selected' : '' }}>🔴 IP Bị Chặn (Blocked)</option>
                    <option value="success" {{ request('level') == 'success' ? 'selected' : '' }}>🟢 IP Được Gỡ (Unblocked)</option>
                </select>

                <input type="date" name="from_date" class="filter-input" value="{{ $fromDate ?? '' }}">
                <span style="color: var(--text-muted);">-</span>
                <input type="date" name="to_date" class="filter-input" value="{{ $toDate ?? '' }}">

                <button type="submit" class="btn-action"><i class="fas fa-search"></i> Lọc dữ liệu</button>
                <a href="{{ route('logs.index') }}" class="btn-action btn-clear" title="Xóa bộ lọc"><i class="fas fa-redo"></i></a>
            </form>

            <div class="scrollable-area" style="max-height: 500px; overflow-y: auto; padding-right: 5px;">
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
                        @forelse($logs as $log)
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
        
        <div class="dashboard-footer">Protected by Aegis Firewall Engine</div>
    </main>

    <script>
        Chart.defaults.color = '#a1a1aa';
        Chart.defaults.font.family = 'Inter';
        Chart.defaults.plugins.tooltip.backgroundColor = '#18181b';
        Chart.defaults.plugins.tooltip.titleColor = '#fafafa';
        Chart.defaults.plugins.tooltip.bodyColor = '#a1a1aa';
        Chart.defaults.plugins.tooltip.borderColor = '#27272a';
        Chart.defaults.plugins.tooltip.borderWidth = 1;
        Chart.defaults.plugins.tooltip.padding = 10;
        Chart.defaults.plugins.tooltip.cornerRadius = 6;

        const ctx = document.getElementById('logChart').getContext('2d');
        
        // Tạo dải màu gradient cho đẹp hơn
        let gradRed = ctx.createLinearGradient(0, 0, 0, 250);
        gradRed.addColorStop(0, 'rgba(248, 113, 113, 0.2)');
        gradRed.addColorStop(1, 'rgba(248, 113, 113, 0)');
        
        let gradGreen = ctx.createLinearGradient(0, 0, 0, 250);
        gradGreen.addColorStop(0, 'rgba(52, 211, 153, 0.2)');
        gradGreen.addColorStop(1, 'rgba(52, 211, 153, 0)');

        new Chart(ctx, {
            type: 'line',
            data: {
                labels: {!! json_encode($chartLabels ?? []) !!},
                datasets: [
                    { 
                        label: 'IP Bị Chặn (Blocked)', 
                        data: {!! json_encode($chartBlocked ?? []) !!}, 
                        borderColor: '#f87171', 
                        backgroundColor: gradRed, 
                        fill: true, tension: 0.4, borderWidth: 2, pointRadius: 0, pointHoverRadius: 4 
                    },
                    { 
                        label: 'IP Gỡ Chặn (Unblocked)', 
                        data: {!! json_encode($chartUnblocked ?? []) !!}, 
                        borderColor: '#34d399', 
                        backgroundColor: gradGreen, 
                        fill: true, tension: 0.4, borderWidth: 2, pointRadius: 0, pointHoverRadius: 4 
                    }
                    // Đã loại bỏ dòng Warning/Alert màu vàng
                ]
            },
            options: { 
                responsive: true, 
                maintainAspectRatio: false,
                interaction: { mode: 'index', intersect: false },
                plugins: { legend: { labels: { usePointStyle: true, boxWidth: 6 } } },
                scales: {
                    x: { border: {display: false}, grid: { display: false } },
                    y: { border: {display: false}, grid: { color: '#18181b', borderDash: [5,5] }, beginAtZero: true, ticks: { stepSize: 1 } }
                }
            }
        });
    </script>
</body>
</html>