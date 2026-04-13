<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    <title>Server Health Monitor - Security System</title>
    
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    <style>
        :root {
            /* Vercel/Linear Deep Dark Theme Palette */
            --bg-main: #000000;
            --bg-card: #0a0a0a;
            --border-color: #27272a;
            --border-hover: #3f3f46;
            
            --text-main: #fafafa;
            --text-muted: #a1a1aa;
            
            /* Vibrant Neon Colors */
            --neon-purple: #c084fc;
            --neon-blue: #38bdf8;
            --neon-green: #34d399;
            --neon-red: #f87171;
            --neon-orange: #fbbf24;
            
            --shadow-soft: 0 10px 30px rgba(0, 0, 0, 0.5);
        }

        * { box-sizing: border-box; }

        body { 
            margin: 0; padding: 0; background-color: var(--bg-main); 
            color: var(--text-main); font-family: 'Inter', -apple-system, sans-serif; 
            display: flex; min-height: 100vh; -webkit-font-smoothing: antialiased;
        }
        
        /* --- SIDEBAR --- */
        .sidebar { 
            width: 72px; background-color: var(--bg-main); border-right: 1px solid var(--border-color); 
            display: flex; flex-direction: column; padding: 24px 0; transition: width 0.2s ease; 
            overflow: hidden; white-space: nowrap; position: fixed; height: 100vh; z-index: 1000;
        }
        .sidebar:hover { width: 240px; background-color: var(--bg-card); }
        .sidebar-item { 
            width: 100%; padding: 16px 0; display: flex; align-items: center; color: var(--text-muted); 
            text-decoration: none; transition: all 0.2s; border-right: 2px solid transparent; 
        }
        .sidebar-icon-wrapper { min-width: 72px; display: flex; justify-content: center; align-items: center; font-size: 1.1rem;}
        .sidebar-item span { opacity: 0; transition: opacity 0.2s; font-size: 14px; font-weight: 500;}
        .sidebar:hover .sidebar-item span { opacity: 1; }
        .sidebar-item.active { 
            color: var(--text-main); background: rgba(255,255,255,0.03);
        }
        .sidebar-item:hover:not(.active) { color: var(--text-main); background: rgba(255,255,255,0.05); }

        /* --- MAIN CONTENT --- */
        .main-content { flex: 1; margin-left: 72px; padding: 40px 48px; display: flex; flex-direction: column; gap: 24px; max-width: 1600px; margin-right: auto;}
        
        .header { display: flex; justify-content: space-between; align-items: center; padding-bottom: 8px;}
        .title-area h1 { font-size: 24px; font-weight: 700; margin: 0; color: var(--text-main); letter-spacing: -0.5px;}
        .title-area p { color: var(--text-muted); margin: 4px 0 0 0; font-size: 14px; font-weight: 400;}
        
        .btn-logout {
            background: transparent; border: 1px solid var(--border-color); color: var(--text-main); 
            padding: 8px 16px; border-radius: 6px; cursor: pointer; font-size: 13px; font-weight: 500;
            transition: all 0.2s; display: flex; align-items: center; gap: 8px;
        }
        .btn-logout:hover { border-color: var(--border-hover); background: rgba(255,255,255,0.05); }

        #system-status-bar { 
            display: inline-flex; gap: 8px; align-items: center; 
            background-color: rgba(52, 211, 153, 0.1); border: 1px solid rgba(52, 211, 153, 0.2); 
            color: var(--neon-green); padding: 6px 12px; border-radius: 9999px; font-weight: 500; font-size: 12px; 
        }
        .status-pulse { width: 8px; height: 8px; background: currentColor; border-radius: 50%; box-shadow: 0 0 8px currentColor; animation: pulse 2s infinite; }
        @keyframes pulse { 0% { opacity: 1; } 50% { opacity: 0.4; } 100% { opacity: 1; } }

        #attack-warning-banner {
            background: rgba(248, 113, 113, 0.1); border: 1px solid rgba(248, 113, 113, 0.2); 
            color: var(--neon-red); padding: 12px 16px; border-radius: 8px; 
            font-weight: 500; font-size: 13px; display: flex; align-items: center; gap: 10px;
        }

        /* --- METRIC CARDS LÀM LẠI CHO ĐẸP --- */
        .cards-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 24px; }
        .metric-card { 
            background: var(--bg-card); 
            border-radius: 12px; 
            padding: 24px; 
            display: flex; 
            flex-direction: column; 
            border: 1px solid var(--border-color); 
            transition: border-color 0.2s ease;
            position: relative;
            overflow: hidden;
        }
        .metric-card::before { content: ''; position: absolute; top: 0; left: 0; width: 100%; height: 3px; }
        .metric-card:hover { border-color: var(--border-hover); }
        
        .card-purple::before { background: var(--neon-purple); }
        .card-blue::before { background: var(--neon-blue); }
        .card-green::before { background: var(--neon-green); }

        .card-header { font-weight: 600; font-size: 14px; color: var(--text-main); display: flex; justify-content: space-between; align-items: center;}
        
        /* GAUGE STYLES */
        .gauge-container { position: relative; height: 130px; display: flex; justify-content: center; align-items: flex-end; margin-top: 15px;}
        .gauge-value { 
            position: absolute; bottom: 5px; text-align: center; width: 100%;
            font-size: 36px; font-weight: 800; color: var(--text-main); line-height: 1; letter-spacing: -1px;
        }
        .gauge-unit { font-size: 16px; color: var(--text-muted); font-weight: 500; margin-left: 4px;}

        .card-footer { display: flex; justify-content: space-between; font-size: 12px; color: var(--text-muted); font-weight: 400; padding-top: 15px; margin-top: 10px; border-top: 1px solid #18181b;}

        .cores-container { display: grid; grid-template-columns: repeat(4, 1fr); gap: 8px; width: 100%; }
        .core-box { background: #18181b; padding: 4px; border-radius: 4px; font-size: 10px; font-weight: 600; text-align: center; color: var(--text-muted);}

        /* --- MIDDLE & BOTTOM LAYOUT --- */
        .middle-section { background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 12px; padding: 24px; display: flex; gap: 32px;}
        .bottom-layout { display: grid; grid-template-columns: 2fr 1fr; gap: 24px; }
        
        .panel { background-color: var(--bg-card); border: 1px solid var(--border-color); border-radius: 12px; padding: 24px; display: flex; flex-direction: column; }
        .panel-header { display: flex; justify-content: space-between; align-items: center; font-size: 14px; font-weight: 600; margin-bottom: 20px; color: var(--text-main);}
        
        .badge-live { background: rgba(56, 189, 248, 0.1); color: var(--neon-blue); padding: 4px 8px; border-radius: 4px; font-size: 10px; font-weight: 600; border: 1px solid rgba(56, 189, 248, 0.2); }

        /* TABLE */
        .process-table { width: 100%; border-collapse: separate; border-spacing: 0; font-size: 13px; text-align: left; }
        .process-table th { padding: 12px 8px; color: var(--text-muted); font-weight: 500; border-bottom: 1px solid var(--border-color); font-size: 12px; text-transform: uppercase;}
        .process-table td { padding: 12px 8px; border-bottom: 1px solid #18181b; color: var(--text-main); font-weight: 400;}
        .process-table tr:last-child td { border-bottom: none; }
        .process-table tr:hover td { background-color: rgba(255,255,255,0.02); }
        .proc-name { font-weight: 500; color: var(--text-main); }

        .dashboard-footer { text-align: right; font-size: 12px; color: var(--text-muted); margin-top: auto;}

        /* Scrollbar */
        .scrollable-area::-webkit-scrollbar { width: 4px; }
        .scrollable-area::-webkit-scrollbar-thumb { background: #3f3f46; border-radius: 4px; }
        
        .spike-item {
            padding: 12px 16px; border-radius: 8px; border: 1px solid var(--border-color); background: rgba(255,255,255,0.02); display: flex; justify-content: space-between; align-items: center;
        }
    </style>
</head>
<body>

    <aside class="sidebar">
        <a href="{{ route('monitor') }}" class="sidebar-item {{ Request::is('monitor*') ? 'active' : '' }}">
            <div class="sidebar-icon-wrapper"><i class="fas fa-layer-group"></i></div>
            <span>Overview</span>
        </a>
        <a href="{{ route('network.index') }}" class="sidebar-item {{ Request::is('network*') ? 'active' : '' }}">
            <div class="sidebar-icon-wrapper"><i class="fas fa-globe"></i></div>
            <span>Network</span>
        </a>
        <a href="{{ route('firewall.index') }}" class="sidebar-item {{ Request::is('firewall*') ? 'active' : '' }}">
            <div class="sidebar-icon-wrapper"><i class="fas fa-shield-alt"></i></div>
            <span>Security</span>
        </a>
        <a href="{{ route('ai.index') }}" class="sidebar-item {{ Request::is('ai-intelligence*') ? 'active' : '' }}">
            <div class="sidebar-icon-wrapper"><i class="fas fa-sparkles"></i></div>
            <span>AI Insight</span>
        </a>
        <a href="{{ route('logs.index') }}" class="sidebar-item {{ Request::is('analytics/logs*') ? 'active' : '' }}">
            <div class="sidebar-icon-wrapper"><i class="fas fa-list-ul"></i></div>
            <span>Logs</span>
        </a>
    </aside>

    <main class="main-content">
        <header class="header">
            <div class="title-area">
                <h1><i class="fas fa-chart-area" style="color: var(--neon-blue);"></i> Audit Logs Explorer</h1>
                <p>Advanced Search, Forensic Analytics & Historic Data</p>
            </div>
            
            <div style="display: flex; gap: 16px; align-items: center;">
                <div class="badge-secure"><i class="fas fa-database"></i> Database Synced</div>
                
                <div style="display: flex; align-items: center; gap: 12px; padding-left: 16px; border-left: 1px solid var(--border-color);">
                    <div style="text-align: right;">
                        <div style="font-size: 14px; font-weight: 600; color: var(--text-main);">
                            {{ Auth::check() ? Auth::user()->name : 'Khách' }}
                        </div>
                        
                        <div style="font-size: 11px; color: var(--neon-green); display: flex; align-items: center; justify-content: flex-end; gap: 4px;">
                            <i class="fas fa-circle" style="font-size: 8px; animation: pulse 2s infinite;"></i> 
                            {{ Auth::check() && Auth::user()->role ? Auth::user()->role : 'System Admin' }}
                        </div>
                    </div>
                    <div style="width: 38px; height: 38px; border-radius: 10px; background: linear-gradient(135deg, var(--neon-blue), var(--neon-purple)); display: flex; justify-content: center; align-items: center; font-weight: bold; font-size: 16px; color: white;">
                        @if(Auth::check() && Auth::user()->name)
                            {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}
                        @else
                            <i class="fas fa-user-shield"></i>
                        @endif
                    </div>
                </div>

                <form action="{{ route('logout') }}" method="POST" style="margin: 0;">
                    @csrf
                    <button type="submit" class="btn-logout" title="Đăng xuất"><i class="fas fa-sign-out-alt"></i> Sign Out</button>
                </form>
            </div>
        </header>

        <div id="attack-warning-banner" style="display:none;">
            <i class="fas fa-exclamation-circle" style="font-size: 18px;"></i> <span>CRITICAL ALERT: System overload or anomaly detected!</span>
        </div>

        <div class="cards-grid">
            <div class="metric-card card-purple">
                <div class="card-header">
                    <span><i class="fas fa-microchip" style="margin-right: 6px; color: var(--neon-purple);"></i> CPU Utilization</span>
                    <div id="cpu-status-icon"><i class="fas fa-check-circle" style="color: var(--neon-green)"></i></div> 
                </div>
                <div class="gauge-container">
                    <canvas id="cpuGauge"></canvas>
                    <div class="gauge-value"><span id="cpu-val">0</span><span class="gauge-unit">%</span></div>
                </div>
                <div class="card-footer">
                    <div id="cores-list" class="cores-container"></div>
                </div>
            </div>

            <div class="metric-card card-blue">
                <div class="card-header">
                    <span><i class="fas fa-memory" style="margin-right: 6px; color: var(--neon-blue);"></i> Memory Usage</span>
                    <div id="ram-status-icon"><i class="fas fa-check-circle" style="color: var(--neon-green)"></i></div>
                </div>
                <div class="gauge-container">
                    <canvas id="ramGauge"></canvas>
                    <div class="gauge-value"><span id="ram-val">0</span><span class="gauge-unit">%</span></div>
                </div>
                <div class="card-footer">
                    <span>Used: <span id="ram-used" style="color:var(--text-main); font-weight:600;">0</span> GB</span>
                    <span>Total: <span id="ram-total">0</span> GB</span>
                </div>
            </div>

            <div class="metric-card card-green">
                <div class="card-header">
                    <span><i class="fas fa-hdd" style="margin-right: 6px; color: var(--neon-green);"></i> Storage (Root)</span>
                    <div id="disk-status-icon"><i class="fas fa-check-circle" style="color: var(--neon-green)"></i></div>
                </div>
                <div class="gauge-container">
                    <canvas id="diskGauge"></canvas>
                    <div class="gauge-value"><span id="disk-val">0</span><span class="gauge-unit">%</span></div>
                </div>
                <div class="card-footer">
                    <span>Free Space: <span id="disk-free" style="color:var(--text-main); font-weight:600;">0</span> GB</span>
                </div>
            </div>
        </div>

        <div class="middle-section">
            <div style="flex: 2.5; display: flex; flex-direction: column;">
                <div class="panel-header" style="margin-bottom: 12px;">
                    <span>Performance History (6H)</span>
                    <span class="badge-live">Live Sync</span>
                </div>
                <div style="flex: 1; height: 240px; width: 100%;">
                    <canvas id="historical24hChart"></canvas>
                </div>
            </div>

            <div style="flex: 1; border-left: 1px solid var(--border-color); padding-left: 32px; display: flex; flex-direction: column;">
                <div class="panel-header" style="margin-bottom: 16px;">
                    <span>Top Anomalies</span>
                </div>
                <div id="spikes-list" class="scrollable-area" style="display: flex; flex-direction: column; gap: 12px; overflow-y: auto; flex:1; padding-right: 8px;">
                    <div style="font-size: 13px; color: var(--text-muted); text-align: center; padding-top: 20px;">
                        <i class="fas fa-spinner fa-spin"></i> Fetching data...
                    </div>
                </div>
            </div>
        </div>

        <div class="bottom-layout">
            <div class="panel">
                <div class="panel-header">
                    <span>Real-time Metrics (60s)</span>
                    <div style="display: flex; gap: 16px; font-size: 13px; font-weight: 600;">
                        <span style="color:var(--text-main)"><span style="background:var(--neon-purple); display:inline-block; width:10px; height:10px; border-radius:50%; margin-right:6px;"></span> CPU</span> 
                        <span style="color:var(--text-main)"><span style="background:var(--neon-blue); display:inline-block; width:10px; height:10px; border-radius:50%; margin-right:6px;"></span> RAM</span>
                    </div>
                </div>
                <div style="height: 300px; width:100%;"><canvas id="historyChart"></canvas></div>
            </div>

            <div class="panel">
                <div class="panel-header">
                    <span>Top Processes</span>
                </div>
                <div class="scrollable-area" style="overflow-y: auto; flex: 1; padding-right: 8px;">
                    <table class="process-table">
                        <thead>
                            <tr><th>PID</th><th>Command</th><th>CPU</th><th>Mem</th></tr>
                        </thead>
                        <tbody id="process-list"></tbody>
                    </table>
                </div>
            </div>
        </div>
        
        <div class="dashboard-footer">Last synced: <span id="last-update-time" style="color:var(--text-main)">--:--:--</span></div>
    </main>

    <script>
        // Cấu hình mặc định cho Chart.js chuẩn Dark Theme
        Chart.defaults.color = '#a1a1aa';
        Chart.defaults.font.family = 'Inter';
        Chart.defaults.plugins.tooltip.backgroundColor = '#18181b';
        Chart.defaults.plugins.tooltip.titleColor = '#fafafa';
        Chart.defaults.plugins.tooltip.bodyColor = '#a1a1aa';
        Chart.defaults.plugins.tooltip.borderColor = '#27272a';
        Chart.defaults.plugins.tooltip.borderWidth = 1;
        Chart.defaults.plugins.tooltip.padding = 10;
        Chart.defaults.plugins.tooltip.cornerRadius = 6;

        // ==========================================
        // 0. GAUGE CHARTS (BIỂU ĐỒ BÁN NGUYỆT)
        // ==========================================
        function createGauge(ctxId, defaultColor) {
            return new Chart(document.getElementById(ctxId).getContext('2d'), {
                type: 'doughnut',
                data: {
                    labels: ['Used', 'Free'],
                    datasets: [{
                        data: [0, 100],
                        backgroundColor: [defaultColor, '#18181b'],
                        borderWidth: 0,
                        circumference: 180,
                        rotation: 270,
                        borderRadius: 4 // Bo tròn 2 đầu thanh
                    }]
                },
                options: {
                    responsive: true, maintainAspectRatio: false,
                    cutout: '80%', // Độ mỏng của thanh
                    plugins: { legend: { display: false }, tooltip: { enabled: false } },
                    animation: { animateRotate: true, animateScale: false }
                }
            });
        }

        let cpuGauge = createGauge('cpuGauge', '#c084fc');
        let ramGauge = createGauge('ramGauge', '#38bdf8');
        let diskGauge = createGauge('diskGauge', '#34d399');

        function updateGaugeData(chart, value, defaultColor, isAlert) {
            let color = isAlert ? '#f87171' : defaultColor;
            chart.data.datasets[0].data = [value, 100 - value];
            chart.data.datasets[0].backgroundColor = [color, '#18181b'];
            chart.update();
        }

        // ==========================================
        // 1. BIỂU ĐỒ REAL-TIME 60S
        // ==========================================
        const ctx = document.getElementById('historyChart').getContext('2d');
        
        let gradPurple = ctx.createLinearGradient(0, 0, 0, 250);
        gradPurple.addColorStop(0, 'rgba(192, 132, 252, 0.2)'); 
        gradPurple.addColorStop(1, 'rgba(192, 132, 252, 0)');
        
        let gradBlue = ctx.createLinearGradient(0, 0, 0, 250);
        gradBlue.addColorStop(0, 'rgba(56, 189, 248, 0.2)'); 
        gradBlue.addColorStop(1, 'rgba(56, 189, 248, 0)');

        let timeLabels = [], cpuData = [], ramData = [];
        const historyChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: timeLabels,
                datasets: [
                    { label: 'CPU', data: cpuData, borderColor: '#c084fc', backgroundColor: gradPurple, tension: 0.4, fill: true, pointRadius: 0, borderWidth: 2 },
                    { label: 'RAM', data: ramData, borderColor: '#38bdf8', backgroundColor: gradBlue, tension: 0.4, fill: true, pointRadius: 0, borderWidth: 2 }
                ]
            },
            options: {
                responsive: true, maintainAspectRatio: false,
                scales: { 
                    y: { beginAtZero: true, max: 100, border: {display: false}, grid: { color: '#18181b', borderDash: [5, 5] } },
                    x: { display: false }
                },
                plugins: { legend: { display: false }, tooltip: { mode: 'index', intersect: false } },
                interaction: { mode: 'nearest', axis: 'x', intersect: false }
            }
        });

        // ==========================================
        // 2. FETCH DATA TỪ API
        // ==========================================
        function updateDashboard() {
            fetch('/api/server-status')
                .then(res => res.json())
                .then(data => {
                    // Update Gauges
                    updateGaugeData(cpuGauge, data.cpu_percent, '#c084fc', data.cpu_percent >= 90);
                    document.getElementById('cpu-val').innerText = data.cpu_percent;
                    
                    updateGaugeData(ramGauge, data.ram_percent, '#38bdf8', data.ram_percent >= 90);
                    document.getElementById('ram-val').innerText = data.ram_percent;
                    
                    updateGaugeData(diskGauge, data.disk_percent, '#34d399', data.disk_percent >= 90);
                    document.getElementById('disk-val').innerText = data.disk_percent;
                    
                    document.getElementById('ram-total').innerText = data.ram_total;
                    document.getElementById('ram-used').innerText = data.ram_used;
                    document.getElementById('disk-free').innerText = data.disk_free;
                    
                    const setStatusIcon = (id, val, limit) => {
                        const el = document.getElementById(id);
                        if (val > limit) {
                            el.innerHTML = `<i class="fas fa-exclamation-circle" style="color: var(--neon-red); font-size:16px;"></i>`;
                        } else {
                            el.innerHTML = `<i class="fas fa-check-circle" style="color: var(--neon-green); font-size:16px;"></i>`;
                        }
                    };
                    setStatusIcon('cpu-status-icon', data.cpu_percent, 80);
                    setStatusIcon('ram-status-icon', data.ram_percent, 85);
                    setStatusIcon('disk-status-icon', data.disk_percent, 90);

                    let coresHtml = '';
                    data.cores.forEach((c, i) => {
                        // Hiện tối đa 4 core cho cân đối
                        if (i < 4) {
                            let color = c.val > 80 ? 'var(--neon-red)' : 'var(--text-muted)';
                            coresHtml += `<div class="core-box"><span style="color:${color}">Core ${i}</span> <span style="font-size:12px; color:var(--text-main)">${c.val}%</span></div>`;
                        }
                    });
                    document.getElementById('cores-list').innerHTML = coresHtml;

                    const banner = document.getElementById('attack-warning-banner');
                    const statusBar = document.getElementById('system-status-bar');
                    const statusText = document.getElementById('status-text');
                    
                    if(data.is_attacked) {
                        banner.style.display = 'flex';
                        statusBar.style.backgroundColor = 'rgba(248,113,113,0.1)'; 
                        statusBar.style.borderColor = 'rgba(248,113,113,0.2)'; 
                        statusBar.style.color = 'var(--neon-red)';
                        statusText.innerText = 'WARNING: HIGH LOAD DETECTED';
                    } else {
                        banner.style.display = 'none';
                        statusBar.style.backgroundColor = 'rgba(52,211,153,0.1)'; 
                        statusBar.style.borderColor = 'rgba(52,211,153,0.2)'; 
                        statusBar.style.color = 'var(--neon-green)';
                        statusText.innerText = 'System Stable';
                    }

                    let tbody = document.getElementById('process-list');
                    tbody.innerHTML = '';
                    if(data.processes) {
                        data.processes.forEach(proc => {
                            tbody.innerHTML += `
                                <tr>
                                    <td style="color: var(--text-muted)">${proc.pid}</td>
                                    <td class="proc-name">${proc.name}</td>
                                    <td style="color: var(--neon-purple); font-weight:500;">${proc.cpu}%</td>
                                    <td style="color: var(--neon-blue); font-weight:500;">${proc.ram}%</td>
                                </tr>
                            `;
                        });
                    }

                    let now = new Date().toLocaleTimeString();
                    document.getElementById('last-update-time').innerText = now;
                    timeLabels.push(now); cpuData.push(data.cpu_percent); ramData.push(data.ram_percent);
                    if (timeLabels.length > 20) { timeLabels.shift(); cpuData.shift(); ramData.shift(); }
                    historyChart.update();
                });
        }
        setInterval(updateDashboard, 5000); 
        updateDashboard();

        // ==========================================
        // 3. BIỂU ĐỒ LỊCH SỬ LƯU TRỮ (6H)
        // ==========================================
        const ctx24h = document.getElementById('historical24hChart').getContext('2d');
        const chart24h = new Chart(ctx24h, {
            type: 'bar',
            data: { labels: [], datasets: [] },
            options: {
                responsive: true, maintainAspectRatio: false,
                interaction: { mode: 'index', intersect: false },
                scales: {
                    y: { max: 100, min: 0, border: {display: false}, grid: { color: '#18181b', borderDash: [5,5] }, ticks: { callback: val => val + '%' } },
                    x: { border: {display: false}, grid: { display: false }, ticks: { maxTicksLimit: 12 } }
                },
                plugins: { legend: { display: false } }
            }
        });

        function updateHistoricalData() {
            fetch('/api/metrics/history')
                .then(res => res.json())
                .then(data => {
                    if(data.history.length === 0) {
                        document.getElementById('spikes-list').innerHTML = '<div style="font-size: 13px; color: var(--neon-green); text-align:center; padding-top:20px;"><i class="fas fa-check-circle"></i> All clear. No spikes recorded.</div>';
                        return;
                    }

                    const histLabels = data.history.map(item => {
                        let d = new Date(item.created_at);
                        return `${d.getHours()}:${d.getMinutes() < 10 ? '0' : ''}${d.getMinutes()}`;
                    });
                    
                    const histCpu = data.history.map(item => item.cpu_percent);
                    // Đổi màu cột Bar chart thành đỏ nếu > 80%
                    const bgColors = histCpu.map(val => val > 80 ? '#f87171' : '#38bdf8');

                    chart24h.data.labels = histLabels;
                    chart24h.data.datasets = [
                        {
                            label: 'CPU Usage', data: histCpu, backgroundColor: bgColors, borderRadius: 4, barThickness: 'flex'
                        }
                    ];
                    chart24h.update();

                    const spikesList = document.getElementById('spikes-list');
                    spikesList.innerHTML = '';
                    
                    data.spikes.forEach(spike => {
                        let d = new Date(spike.created_at);
                        let timeStr = `${d.getHours()}:${d.getMinutes() < 10 ? '0' : ''}${d.getMinutes()} - ${d.getDate()}/${d.getMonth()+1}`;
                        let isCrit = spike.cpu_percent > 90;
                        let colorVar = isCrit ? 'var(--neon-red)' : 'var(--neon-orange)';

                        spikesList.innerHTML += `
                            <div class="spike-item">
                                <div>
                                    <div style="color: var(--text-main); font-weight: 600; font-size: 13px;">CPU Spike</div>
                                    <div style="color: var(--text-muted); font-size: 12px; margin-top: 4px; font-weight: 500;">
                                        <i class="fas fa-clock" style="margin-right:4px;"></i> ${timeStr}
                                    </div>
                                </div>
                                <div style="color: ${colorVar}; font-weight: 700; font-size: 16px; background: rgba(255,255,255,0.05); padding: 6px 12px; border-radius: 6px;">
                                    ${spike.cpu_percent}%
                                </div>
                            </div>
                        `;
                    });
                }).catch(err => console.error(err));
        }
        updateHistoricalData();
        setInterval(updateHistoricalData, 300000); 

    </script>
</body>
</html>