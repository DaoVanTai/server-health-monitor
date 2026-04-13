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
        :root { --bg-main: #000000; --bg-card: #0a0a0a; --border-color: #27272a; --text-main: #fafafa; --text-muted: #a1a1aa; --neon-blue: #38bdf8; --neon-red: #f87171; --neon-green: #34d399; --neon-orange: #fbbf24; }
        body { margin: 0; background-color: var(--bg-main); color: var(--text-main); font-family: 'Inter', sans-serif; display: flex; min-height: 100vh; }
        .sidebar { width: 72px; background-color: var(--bg-main); border-right: 1px solid var(--border-color); display: flex; flex-direction: column; padding: 20px 0; transition: width 0.2s ease; overflow: hidden; white-space: nowrap; position: fixed; height: 100vh; z-index: 1000; }
        .sidebar:hover { width: 240px; background-color: var(--bg-card); }
        .sidebar-item { width: 100%; padding: 16px 0; display: flex; align-items: center; color: var(--text-muted); text-decoration: none; transition: 0.2s; }
        .sidebar-icon-wrapper { min-width: 72px; display: flex; justify-content: center; font-size: 1.1rem; }
        .sidebar-item span { opacity: 0; font-size: 14px; transition: 0.2s; }
        .sidebar:hover .sidebar-item span { opacity: 1; }
        .sidebar-item.active { color: var(--text-main); background: rgba(255,255,255,0.05); }
        .main-content { flex: 1; margin-left: 72px; padding: 40px 48px; max-width: 1600px; }
        .header { display: flex; justify-content: space-between; align-items: center; padding-bottom: 20px; }
        .panel { background-color: var(--bg-card); border: 1px solid var(--border-color); border-radius: 12px; padding: 24px; margin-bottom: 24px; }
        .log-table { width: 100%; border-collapse: collapse; font-size: 13px; text-align: left; }
        .log-table th { padding: 12px; color: var(--text-muted); border-bottom: 1px solid var(--border-color); }
        .log-table td { padding: 14px 12px; border-bottom: 1px solid #18181b; }
        .badge { padding: 4px 8px; border-radius: 4px; font-size: 11px; font-weight: bold; }
        .badge-danger { background: rgba(248, 113, 113, 0.1); color: var(--neon-red); border: 1px solid rgba(248, 113, 113, 0.2); } 
        .badge-success { background: rgba(52, 211, 153, 0.1); color: var(--neon-green); border: 1px solid rgba(52, 211, 153, 0.2); } 
        .badge-warning { background: rgba(251, 191, 36, 0.1); color: var(--neon-orange); border: 1px solid rgba(251, 191, 36, 0.2); } 
    </style>
</head>
<body>
    <aside class="sidebar">
        <div class="sidebar-logo-container" style="padding: 0 16px; margin-bottom: 24px;">
            <div style="min-width: 40px; height: 40px; border-radius: 10px; background: linear-gradient(135deg, var(--neon-cyan), var(--neon-purple)); display: flex; justify-content: center; align-items: center; font-size: 20px; color: white;"><i class="fas fa-shield-virus"></i></div>
        </div>
        <a href="{{ route('monitor') }}" class="sidebar-item"><div class="sidebar-icon-wrapper"><i class="fas fa-layer-group"></i></div><span>Overview</span></a>
        <a href="{{ route('network.index') }}" class="sidebar-item"><div class="sidebar-icon-wrapper"><i class="fas fa-globe"></i></div><span>Network</span></a>
        <a href="{{ route('firewall.index') }}" class="sidebar-item"><div class="sidebar-icon-wrapper"><i class="fas fa-shield-alt"></i></div><span>Security</span></a>
        <a href="{{ route('ai.index') }}" class="sidebar-item"><div class="sidebar-icon-wrapper"><i class="fas fa-sparkles"></i></div><span>AI Insight</span></a>
        <a href="{{ route('logs.index') }}" class="sidebar-item active"><div class="sidebar-icon-wrapper"><i class="fas fa-list-ul"></i></div><span>Audit Logs Explorer</span></a>
    </aside>

    <main class="main-content">
        <header class="header">
            <div>
                <h1 style="margin:0; font-size: 24px;"><i class="fas fa-chart-area" style="color: var(--neon-blue);"></i> Audit Logs Explorer</h1>
                <p style="margin:5px 0 0; color: var(--text-muted);">Advanced Search, Forensic Analytics & Historic Data</p>
            </div>
        </header>

        <div class="panel">
            <h3 style="margin-top:0; font-size: 14px;"><i class="fas fa-chart-line"></i> Events Overview (Last 7 Days)</h3>
            <div style="height: 250px; width: 100%;"><canvas id="logChart"></canvas></div>
        </div>

        <div class="panel">
            <h3 style="margin-top:0; font-size: 14px;"><i class="fas fa-filter"></i> Detailed Logs Explorer</h3>
            <table class="log-table">
                <thead><tr><th>Thời gian</th><th>Phân loại</th><th>Nội dung chi tiết</th><th>Target IP</th></tr></thead>
                <tbody>
                    @forelse($logs as $log)
                    <tr>
                        <td style="color: var(--text-muted);">{{ $log->created_at->format('H:i:s d/m') }}</td>
                        <td>
                            @if($log->level == 'danger') <span class="badge badge-danger"><i class="fas fa-lock"></i> blocked</span>
                            @elseif($log->level == 'success') <span class="badge badge-success"><i class="fas fa-unlock"></i> unblocked</span>
                            @elseif($log->level == 'warning') <span class="badge badge-warning"><i class="fas fa-exclamation-triangle"></i> alert</span>
                            @endif
                        </td>
                        <td><strong>[{{ $log->source }}]</strong><br>{{ $log->message }}</td>
                        <td style="font-family: monospace;">{{ $log->ip_address ?? 'N/A' }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="4" style="text-align: center;">Không có dữ liệu.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </main>

    <script>
        Chart.defaults.color = '#a1a1aa';
        const ctx = document.getElementById('logChart').getContext('2d');
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: {!! json_encode($chartLabels) !!},
                datasets: [
                    { label: 'Blocked', data: {!! json_encode($chartBlocked) !!}, borderColor: '#f87171', backgroundColor: 'rgba(248, 113, 113, 0.1)', fill: true, tension: 0.4 },
                    { label: 'Unblocked', data: {!! json_encode($chartUnblocked) !!}, borderColor: '#34d399', backgroundColor: 'rgba(52, 211, 153, 0.1)', fill: true, tension: 0.4 },
                    { label: 'Alert', data: {!! json_encode($chartAlert) !!}, borderColor: '#fbbf24', backgroundColor: 'rgba(251, 191, 36, 0.1)', fill: true, tension: 0.4 }
                ]
            },
            options: { responsive: true, maintainAspectRatio: false }
        });
    </script>
</body>
</html>