<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Server Health Monitor - (Server Health Monitoring & Detection System)</title>
    
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <style>
        :root {
            --bg-main: #0b1120;
            --bg-card: #111827;
            --border-color: #1f2937;
            --text-main: #f3f4f6;
            --text-muted: #9ca3af;
            --neon-purple: #a855f7;
            --neon-blue: #3b82f6;
            --neon-green: #22c55e;
            --neon-red: #ef4444;
            --neon-orange: #f59e0b;
        }

        body { margin: 0; padding: 0; background-color: var(--bg-main); color: var(--text-main); font-family: 'Segoe UI', sans-serif; display: flex; min-height: 100vh; }
        
        /* --- SIDEBAR --- */
        .sidebar { width: 70px; background-color: #0f172a; border-right: 1px solid var(--border-color); display: flex; flex-direction: column; padding: 20px 0; transition: width 0.3s cubic-bezier(0.4, 0, 0.2, 1); overflow: hidden; white-space: nowrap; position: fixed; height: 100vh; z-index: 1000; }
        .sidebar:hover { width: 220px; box-shadow: 10px 0 30px rgba(0,0,0,0.5); }
        .sidebar-item { width: 100%; padding: 15px 0; display: flex; align-items: center; color: var(--text-muted); text-decoration: none; transition: all 0.2s; border-left: 3px solid transparent; }
        .sidebar-icon-wrapper { min-width: 70px; display: flex; justify-content: center; align-items: center; }
        .sidebar-item span { opacity: 0; transform: translateX(-10px); transition: all 0.3s; font-size: 14px; font-weight: 500; }
        .sidebar:hover .sidebar-item span { opacity: 1; transform: translateX(0); }
        .sidebar-item.active { color: var(--neon-blue); border-left: 3px solid var(--neon-blue); background: rgba(59, 130, 246, 0.05); }
        .sidebar-item:hover { color: var(--text-main); }

        /* --- NỘI DUNG CHÍNH --- */
        .main-content { flex: 1; margin-left: 70px; padding: 30px 40px; display: flex; flex-direction: column; gap: 20px; }
        .header { display: flex; justify-content: space-between; align-items: flex-start; }
        .title-area h1 { font-size: 28px; letter-spacing: 2px; margin: 0; text-transform: uppercase; }
        .title-area p { color: var(--text-muted); margin: 5px 0 0 0; font-size: 14px; }
        
        .cards-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; }
        .metric-card { background-color: var(--bg-card); border-radius: 12px; padding: 20px; display: flex; flex-direction: column; gap: 15px; border: 1px solid var(--border-color); transition: all 0.3s ease; }
        .metric-card:hover { transform: translateY(-5px); border-color: var(--neon-blue); }

        .main-value { font-size: 32px; font-weight: bold; margin: 0; }
        .card-purple .main-value { color: var(--neon-purple); }
        .card-blue .main-value { color: var(--neon-blue); }
        .card-green .main-value { color: var(--neon-green); }

        .status-ok { color: var(--neon-green); filter: drop-shadow(0 0 3px var(--neon-green)); }
        .status-warning { color: var(--neon-red); filter: drop-shadow(0 0 5px var(--neon-red)); animation: pulse-warn 1s infinite; }
        @keyframes pulse-warn { 0% { opacity: 1; } 50% { opacity: 0.4; } 100% { opacity: 1; } }

        .cores-container { display: grid; grid-template-columns: repeat(4, 1fr); gap: 5px; margin-top: 10px; }
        .core-box { background: rgba(255,255,255,0.03); padding: 5px; border-radius: 4px; font-size: 9px; text-align: center; border: 1px solid rgba(255,255,255,0.05); }
        
        .css-progress-track { height: 6px; background-color: rgba(255,255,255,0.05); border-radius: 3px; overflow: hidden; margin: 10px 0; }
        .css-progress-fill { height: 100%; transition: width 0.5s ease; width: 0%; }
        
        .bottom-section { display: flex; gap: 20px; }
        .chart-section { background-color: var(--bg-card); border: 1px solid var(--border-color); border-radius: 12px; padding: 20px; flex: 1.8; min-height: 350px; }
        .processes-section { background-color: var(--bg-card); border: 1px solid var(--border-color); border-radius: 12px; padding: 20px; flex: 1.2; }
        
        .process-table { width: 100%; border-collapse: collapse; font-size: 12px; margin-top: 15px; }
        .process-table th { text-align: left; color: var(--text-muted); padding-bottom: 10px; border-bottom: 1px solid var(--border-color); }
        .process-table td { padding: 10px 0; border-bottom: 1px solid rgba(255,255,255,0.02); }

        .dashboard-footer { text-align: right; font-size: 11px; color: var(--text-muted); }

        /* Bổ sung class cho phần 24H */
        .badge-live { background: rgba(245, 158, 11, 0.1); color: var(--neon-orange); padding: 4px 10px; border-radius: 4px; font-size: 10px; font-weight: bold; border: 1px solid var(--neon-orange); }
        .historical-section { background-color: var(--bg-card); border: 1px solid var(--border-color); border-radius: 12px; padding: 20px; margin: 10px 0; display: flex; gap: 20px; transition: all 0.3s ease; }
        .historical-section:hover { border-color: var(--neon-orange); box-shadow: 0 0 15px rgba(245, 158, 11, 0.1); }
    </style>
</head>
<body>

    <aside class="sidebar">
        <a href="{{ route('monitor') }}" class="sidebar-item {{ Request::is('monitor*') ? 'active' : '' }}">
            <div class="sidebar-icon-wrapper">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M3 12a9 9 0 1 1 18 0M12 7v5l3 3"></path></svg>
            </div>
            <span>Dashboard</span>
        </a>
        <a href="{{ route('network.index') }}" class="sidebar-item {{ Request::is('network*') ? 'active' : '' }}">
            <div class="sidebar-icon-wrapper">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="2" y1="12" x2="22" y2="12"></line><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"></path></svg>
            </div>
            <span>Network Center</span>
        </a>
        <a href="#" class="sidebar-item">
            <div class="sidebar-icon-wrapper">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path></svg>
            </div>
            <span>Security</span>
        </a>
    </aside>

    <main class="main-content">
        <header class="header">
            <div class="title-area">
                <h1>SYSTEM DASHBOARD</h1>
                <p>Real-time Core Resource Monitoring</p>
            </div>
            
            <div style="display: flex; gap: 15px; align-items: center;">
                <form action="{{ route('logout') }}" method="POST" style="margin: 0;">
                    @csrf
                    <button type="submit" style="background: none; border: 1px solid var(--neon-red); color: var(--neon-red); padding: 5px 12px; border-radius: 4px; cursor: pointer; font-size: 11px; font-weight: bold;">Log Out</button>
                </form>
                <div style="position: relative;">
                    <button onclick="toggleChat()" style="background: var(--neon-blue); border: none; border-radius: 50%; width: 40px; height: 40px; cursor: pointer; box-shadow: 0 0 10px var(--neon-blue); color: white;">💬</button>
                </div>
            </div>
        </header>

        <div id="attack-warning-banner" style="display:none; background-color:rgba(239,68,68,0.1); border:1px solid var(--neon-red); color:var(--neon-red); padding:15px; border-radius:6px; margin-bottom:10px; text-align:center; font-weight:bold;">[!] ALERT: SYSTEM OVERLOAD DETECTED [!]</div>

        <div class="cards-grid">
            <div class="metric-card card-purple">
                <div style="display:flex; justify-content: space-between; align-items:center">
                    <span style="font-weight:bold; font-size:12px; color:var(--text-muted)">CPU LOAD</span>
                    <div id="cpu-status-icon"></div> 
                </div>
                <div class="main-value"><span id="cpu-main">0</span>%</div>
                <div class="css-progress-track"><div id="cpu-bar" class="css-progress-fill" style="background: var(--neon-purple)"></div></div>
                <div id="cores-list" class="cores-container"></div>
            </div>

            <div class="metric-card card-blue">
                <div style="display:flex; justify-content: space-between; align-items:center">
                    <span style="font-weight:bold; font-size:12px; color:var(--text-muted)">RAM USAGE</span>
                    <div id="ram-status-icon"></div>
                </div>
                <div class="main-value"><span id="ram-main">0</span>%</div>
                <div class="css-progress-track"><div id="ram-bar" class="css-progress-fill" style="background: var(--neon-blue)"></div></div>
                <div style="font-size: 10px; color: var(--text-muted); display: flex; justify-content: space-between;">
                    <span>Total: <span id="ram-total">0</span>GB</span>
                    <span>Used: <span id="ram-used">0</span>GB</span>
                </div>
            </div>

            <div class="metric-card card-green">
                <div style="display:flex; justify-content: space-between; align-items:center">
                    <span style="font-weight:bold; font-size:12px; color:var(--text-muted)">DISK CAPACITY</span>
                    <div id="disk-status-icon"></div>
                </div>
                <div class="main-value"><span id="disk-main">0</span>%</div>
                <div class="css-progress-track"><div id="disk-bar" class="css-progress-fill" style="background: var(--neon-green)"></div></div>
                <div style="font-size: 10px; color: var(--text-muted);">Free: <span id="disk-free">0</span>GB</div>
            </div>
        </div>

        <div class="historical-section">
            <div style="flex: 2.5; display: flex; flex-direction: column;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                    <div style="font-weight:bold; color: var(--neon-orange); font-size: 14px; letter-spacing: 1px;">
                        <i class="fas fa-history"></i> HISTORICAL RESOURCE SPIKES (24H ANALYSIS)
                    </div>
                    <span class="badge-live">Last 24 Hours</span>
                </div>
                <div style="flex: 1; height: 200px;">
                    <canvas id="historical24hChart"></canvas>
                </div>
            </div>

            <div style="flex: 1; border-left: 1px solid var(--border-color); padding-left: 20px; display: flex; flex-direction: column;">
                <div style="font-weight:bold; font-size: 12px; color: var(--text-muted); margin-bottom: 15px;">TOP 3 CPU SPIKES</div>
                <div id="spikes-list" style="display: flex; flex-direction: column; gap: 10px; overflow-y: auto;">
                    <div style="font-size: 12px; color: var(--text-muted); text-align: center; padding-top: 20px;">
                        <i class="fas fa-spinner fa-spin"></i> Đang tải dữ liệu...
                    </div>
                </div>
            </div>
        </div>

        <div class="bottom-section">
            <div class="chart-section">
                <div style="font-weight:bold; margin-bottom: 20px;">CORE TRENDS (60s)</div>
                <div style="height: 280px;"><canvas id="historyChart"></canvas></div>
            </div>

            <div class="processes-section">
                <div style="font-weight:bold;">● LIVE PROCESS MONITOR</div>
                <table class="process-table">
                    <thead>
                        <tr><th>PID</th><th>PROCESS</th><th>CPU</th><th>RAM</th></tr>
                    </thead>
                    <tbody id="process-list"></tbody>
                </table>
            </div>
        </div>
        
        <div class="dashboard-footer">Last sync: <span id="last-update-time">--:--:--</span></div>
    </main>

    <script>
        // ==========================================
        // 1. BIỂU ĐỒ REAL-TIME 60S (Giữ nguyên của bạn)
        // ==========================================
        const ctx = document.getElementById('historyChart').getContext('2d');
        let timeLabels = [], cpuData = [], ramData = [];
        const historyChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: timeLabels,
                datasets: [
                    { label: 'CPU', data: cpuData, borderColor: '#a855f7', tension: 0.4, fill: false, borderWidth: 2, pointRadius: 0 },
                    { label: 'RAM', data: ramData, borderColor: '#3b82f6', tension: 0.4, fill: false, borderWidth: 2, pointRadius: 0 }
                ]
            },
            options: {
                responsive: true, maintainAspectRatio: false,
                scales: { 
                    y: { beginAtZero: true, max: 100, grid: { color: 'rgba(255,255,255,0.05)' }, ticks: { color: '#9ca3af' } },
                    x: { display: false }
                },
                plugins: { legend: { display: false } }
            }
        });

        function updateDashboard() {
            fetch('/api/server-status')
                .then(res => res.json())
                .then(data => {
                    document.getElementById('cpu-main').innerText = data.cpu_percent;
                    document.getElementById('cpu-bar').style.width = data.cpu_percent + '%';
                    document.getElementById('ram-main').innerText = data.ram_percent;
                    document.getElementById('ram-bar').style.width = data.ram_percent + '%';
                    document.getElementById('ram-total').innerText = data.ram_total;
                    document.getElementById('ram-used').innerText = data.ram_used;
                    document.getElementById('disk-main').innerText = data.disk_percent;
                    document.getElementById('disk-bar').style.width = data.disk_percent + '%';
                    document.getElementById('disk-free').innerText = data.disk_free;
                    
                    const setStatusIcon = (id, val, limit) => {
                        const el = document.getElementById(id);
                        if (val > limit) {
                            el.innerHTML = `<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" class="status-warning"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0zM12 9v4M12 17h.01"></path></svg>`;
                        } else {
                            el.innerHTML = `<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" class="status-ok"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>`;
                        }
                    };
                    setStatusIcon('cpu-status-icon', data.cpu_percent, 80);
                    setStatusIcon('ram-status-icon', data.ram_percent, 85);
                    setStatusIcon('disk-status-icon', data.disk_percent, 90);

                    let coresHtml = '';
                    data.cores.forEach(c => {
                        coresHtml += `<div class="core-box">${c.name}<div class="css-progress-track" style="height:3px; margin:2px 0"><div class="css-progress-fill" style="width:${c.val}%; background:#a855f7"></div></div></div>`;
                    });
                    document.getElementById('cores-list').innerHTML = coresHtml;

                    document.getElementById('attack-warning-banner').style.display = data.is_attacked ? 'block' : 'none';

                    let procHtml = '';
                    data.processes.forEach(p => {
                        procHtml += `<tr><td>#${p.pid}</td><td style="font-weight:bold">${p.name}</td><td>${p.cpu}%</td><td>${p.ram}%</td></tr>`;
                    });
                    document.getElementById('process-list').innerHTML = procHtml;

                    let now = new Date().toLocaleTimeString();
                    document.getElementById('last-update-time').innerText = now;
                    timeLabels.push(now); cpuData.push(data.cpu_percent); ramData.push(data.ram_percent);
                    if (timeLabels.length > 15) { timeLabels.shift(); cpuData.shift(); ramData.shift(); }
                    historyChart.update();
                });
        }
        setInterval(updateDashboard, 5000); 
        updateDashboard();

        // ==========================================
        // 2. BIỂU ĐỒ LỊCH SỬ 24H (MỚI THÊM)
        // ==========================================
        const ctx24h = document.getElementById('historical24hChart').getContext('2d');
        const chart24h = new Chart(ctx24h, {
            type: 'line',
            data: { labels: [], datasets: [] },
            options: {
                responsive: true, maintainAspectRatio: false,
                interaction: { mode: 'index', intersect: false },
                scales: {
                    y: { max: 100, min: 0, grid: { color: 'rgba(255,255,255,0.05)' }, ticks: { color: '#9ca3af', font: {size: 10}, callback: val => val + '%' } },
                    x: { grid: { display: false }, ticks: { color: '#9ca3af', font: {size: 10}, maxTicksLimit: 12 } }
                },
                plugins: { 
                    legend: { display: false },
                    tooltip: {
                        callbacks: { label: context => `${context.dataset.label}: ${context.parsed.y}%` }
                    }
                }
            }
        });

        function updateHistoricalData() {
            fetch('/api/metrics/history')
                .then(res => res.json())
                .then(data => {
                    // Nếu chưa có dữ liệu (mới bật cronjob)
                    if(data.history.length === 0) {
                        document.getElementById('spikes-list').innerHTML = '<div style="font-size: 12px; color: var(--neon-green); text-align:center;">Hệ thống ổn định. Chưa có cảnh báo.</div>';
                        return;
                    }

                    // Format giờ cho trục X
                    const histLabels = data.history.map(item => {
                        let d = new Date(item.created_at);
                        return `${d.getHours()}:${d.getMinutes() < 10 ? '0' : ''}${d.getMinutes()}`;
                    });
                    
                    const histCpu = data.history.map(item => item.cpu_percent);
                    const histRam = data.history.map(item => item.ram_percent);

                    // Xử lý "dấu chấm phát sáng" (Spike Markers) nếu CPU > 80%
                    const cpuRadii = histCpu.map(val => val > 80 ? 5 : 0);
                    const cpuColors = histCpu.map(val => val > 80 ? '#ef4444' : '#f59e0b');

                    chart24h.data.labels = histLabels;
                    chart24h.data.datasets = [
                        {
                            label: 'CPU Usage',
                            data: histCpu,
                            borderColor: '#f59e0b', // Màu cam
                            backgroundColor: 'rgba(245, 158, 11, 0.1)',
                            borderWidth: 2,
                            tension: 0.4,
                            fill: true,
                            pointRadius: cpuRadii, // Hiện chấm đỏ nếu quá tải
                            pointBackgroundColor: cpuColors,
                            pointBorderColor: '#fff'
                        },
                        {
                            label: 'RAM Usage',
                            data: histRam,
                            borderColor: '#3b82f6', // Màu xanh
                            borderWidth: 2,
                            tension: 0.4,
                            pointRadius: 0 // Ẩn điểm cho đỡ rối mắt
                        }
                    ];
                    chart24h.update();

                    // Cập nhật danh sách Top 3 Spikes bên phải
                    const spikesList = document.getElementById('spikes-list');
                    spikesList.innerHTML = '';
                    
                    data.spikes.forEach(spike => {
                        let d = new Date(spike.created_at);
                        let timeStr = `${d.getHours()}:${d.getMinutes() < 10 ? '0' : ''}${d.getMinutes()} - ${d.getDate()}/${d.getMonth()+1}`;
                        
                        // Đổi màu cảnh báo dựa trên mức độ (%)
                        let colorVar = spike.cpu_percent > 90 ? 'var(--neon-red)' : 'var(--neon-orange)';
                        let bgVar = spike.cpu_percent > 90 ? 'rgba(239, 68, 68, 0.1)' : 'rgba(245, 158, 11, 0.1)';

                        spikesList.innerHTML += `
                            <div style="background: ${bgVar}; border-left: 3px solid ${colorVar}; padding: 10px; border-radius: 4px; display: flex; justify-content: space-between; align-items: center;">
                                <div>
                                    <div style="color: var(--text-main); font-weight: bold; font-size: 13px;">${timeStr}</div>
                                    <div style="color: ${colorVar}; font-size: 11px; margin-top: 4px;">
                                        <i class="fas fa-fire"></i> CPU Spiked to ${spike.cpu_percent}%
                                    </div>
                                </div>
                            </div>
                        `;
                    });
                })
                .catch(err => console.error("Lỗi lấy dữ liệu lịch sử: ", err));
        }
        
        // Cập nhật lịch sử ngay khi load trang và mỗi 5 phút một lần
        updateHistoricalData();
        setInterval(updateHistoricalData, 300000); 

        function toggleChat() { alert("AI Assistant is ready!"); }
    </script>
</body>
</html>