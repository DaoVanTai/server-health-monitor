<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Server Health Monitor - Pro Version</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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

        body { margin: 0; padding: 0; background-color: var(--bg-main); color: var(--text-main); font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; display: flex; min-height: 100vh; }
        .sidebar { width: 60px; background-color: #0f172a; border-right: 1px solid var(--border-color); display: flex; flex-direction: column; align-items: center; padding-top: 20px; gap: 20px; }
        .sidebar-item { width: 24px; height: 24px; background-color: var(--border-color); border-radius: 4px; opacity: 0.5; }
        .sidebar-item.active { background-color: var(--neon-blue); opacity: 1; }
        
        .main-content { flex: 1; padding: 30px 40px; display: flex; flex-direction: column; gap: 20px; }
        .header { display: flex; justify-content: space-between; align-items: flex-start; }
        .title-area h1 { font-size: 28px; letter-spacing: 2px; margin: 0 0 5px 0; text-transform: uppercase; }
        .title-area p { color: var(--text-muted); margin: 0; font-size: 14px; }
        .top-right-stats { display: flex; gap: 20px; font-size: 12px; color: var(--text-muted); text-align: right; align-items: center; }
        .info-row { font-size: 12px; color: var(--text-muted); border-bottom: 1px solid var(--border-color); padding-bottom: 15px; }

        /* Status & Warning */
        #attack-warning-banner { display: none; background-color: rgba(239, 68, 68, 0.1); border: 1px solid var(--neon-red); color: var(--neon-red); padding: 15px 20px; border-radius: 6px; font-weight: bold; text-align: center; letter-spacing: 1px; text-transform: uppercase; animation: pulse-red 1.5s infinite; }
        @keyframes pulse-red { 0% { box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.4); } 70% { box-shadow: 0 0 0 10px rgba(239, 68, 68, 0); } 100% { box-shadow: 0 0 0 0 rgba(239, 68, 68, 0); } }
        #system-status-bar { display: flex; justify-content: flex-start; gap: 15px; align-items: center; background-color: rgba(34, 197, 94, 0.1); border: 1px solid rgba(34, 197, 94, 0.3); color: var(--neon-green); padding: 12px 20px; border-radius: 6px; font-weight: bold; font-size: 14px; }

        /* Cards Grid - Đã đổi sang 4 cột */
        .cards-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; }
        .metric-card { background-color: var(--bg-card); border-radius: 12px; padding: 15px; display: flex; flex-direction: column; gap: 12px; border: 1px solid var(--border-color); transition: all 0.3s ease; }
        .metric-card:hover { transform: translateY(-5px); border-color: var(--neon-blue); }
        
        .card-purple { border-color: var(--neon-purple); }
        .card-blue { border-color: var(--neon-blue); }
        .card-green { border-color: var(--neon-green); }
        .card-orange { border-color: var(--neon-orange); }

        .main-value { font-size: 32px; font-weight: bold; margin: 0; line-height: 1; }
        .card-purple .main-value { color: var(--neon-purple); text-shadow: 0 0 10px rgba(168, 85, 247, 0.3); }
        .card-blue .main-value { color: var(--neon-blue); text-shadow: 0 0 10px rgba(59, 130, 246, 0.3); }
        .card-green .main-value { color: var(--neon-green); text-shadow: 0 0 10px rgba(34, 197, 94, 0.3); }
        .card-orange .main-value { color: var(--neon-orange); text-shadow: 0 0 10px rgba(245, 158, 11, 0.3); }

        /* CPU Cores Grid */
        .cores-container { display: grid; grid-template-columns: repeat(4, 1fr); gap: 5px; margin-top: 5px; }
        .core-box { background: rgba(255,255,255,0.03); padding: 4px; border-radius: 4px; font-size: 9px; text-align: center; border: 1px solid rgba(255,255,255,0.05); }
        .core-bar-mini { height: 3px; background: #222; border-radius: 2px; margin-top: 3px; overflow: hidden; }
        .core-fill { height: 100%; background: var(--neon-purple); width: 0%; transition: width 0.5s; }

        .css-progress-track { height: 6px; background-color: rgba(255,255,255,0.05); border-radius: 3px; overflow: hidden; }
        .css-progress-fill { height: 100%; transition: width 0.5s ease; width: 0%; }
        #cpu-bar { background: var(--neon-purple); }
        #ram-bar { background: var(--neon-blue); }
        #disk-bar { background: var(--neon-green); }
        #net-bar { background: var(--neon-orange); }

        /* Bottom Section */
        .bottom-section { display: flex; gap: 20px; }
        .chart-section { background-color: var(--bg-card); border: 1px solid var(--border-color); border-radius: 12px; padding: 20px; flex: 1.8; min-height: 350px; }
        
        /* Processes Table Pro */
        .processes-section { background-color: var(--bg-card); border: 1px solid var(--border-color); border-radius: 12px; padding: 20px; flex: 1.2; display: flex; flex-direction: column; }
        .process-table-container { flex: 1; overflow-y: auto; margin-top: 15px; }
        .process-table-container::-webkit-scrollbar { width: 4px; }
        .process-table-container::-webkit-scrollbar-thumb { background: var(--border-color); border-radius: 10px; }
        
        .process-table { width: 100%; border-collapse: collapse; font-size: 12px; }
        .process-table th { position: sticky; top: 0; background: var(--bg-card); color: var(--text-muted); text-align: left; padding-bottom: 10px; border-bottom: 1px solid var(--border-color); }
        .process-table td { padding: 10px 0; border-bottom: 1px solid rgba(255,255,255,0.03); }

        .mini-bar-bg { width: 100%; height: 4px; background: rgba(255,255,255,0.05); border-radius: 2px; margin-top: 4px; }
        .mini-bar-fill { height: 100%; border-radius: 2px; transition: width 0.5s; }

        .dashboard-footer { text-align: right; font-size: 11px; color: var(--text-muted); margin-top: 10px; }
    </style>
</head>
<body>

    <aside class="sidebar">
        <div class="sidebar-item"></div><div class="sidebar-item active"></div>
        <div class="sidebar-item"></div><div class="sidebar-item"></div><div class="sidebar-item"></div>
    </aside>

    <main class="main-content">
        <header class="header">
            <div class="title-area">
                <h1>SERVER HEALTH MONITOR</h1>
                <p>Advanced Real-time Detection System // Project: {{ $project_name ?? 'Server Monitoring' }}</p>
            </div>
            
            <div class="top-right-stats">
                <div><div>CORES</div><strong style="color: var(--neon-purple);">ACTIVE</strong></div>
                <div><div>NETWORK</div><strong style="color: var(--neon-orange);">UP</strong></div>
                
                <form action="{{ route('logout') }}" method="POST" style="margin: 0;">
                    @csrf
                    <button type="submit" style="background: none; border: 1px solid var(--neon-red); color: var(--neon-red); padding: 5px 12px; border-radius: 4px; cursor: pointer; font-size: 11px; font-weight: bold;">Log Out</button>
                </form>

                <div style="position: relative;">
                    <button onclick="toggleChat()" style="background: var(--neon-blue); border: none; border-radius: 50%; width: 40px; height: 40px; cursor: pointer; box-shadow: 0 0 10px var(--neon-blue); color: white;">💬</button>
                    <div id="chat-window" style="position: absolute; top: 50px; right: 0; display: none; width: 320px; height: 400px; background: var(--bg-card); border: 1px solid var(--neon-blue); border-radius: 12px; flex-direction: column; z-index: 1000; box-shadow: 0 10px 30px rgba(0,0,0,0.5);">
                        <div style="background: var(--neon-blue); padding: 10px; font-weight: bold; color: white; text-align: center; font-size: 12px;">SYSTEM AI ASSISTANT</div>
                        <div id="chat-content" style="flex: 1; padding: 15px; overflow-y: auto; font-size: 13px; display: flex; flex-direction: column; gap: 10px;"></div>
                        <div style="padding: 10px; border-top: 1px solid var(--border-color); display: flex; gap: 5px;">
                            <input id="chat-input" type="text" placeholder="Nhập lệnh..." style="flex: 1; background: #0b1120; border: 1px solid var(--border-color); color: white; padding: 5px; border-radius: 4px; outline: none;" onkeypress="if(event.key === 'Enter') sendMessage()">
                            <button onclick="sendMessage()" style="background: var(--neon-blue); border: none; color: white; padding: 5px 10px; border-radius: 4px; cursor: pointer;">GỬI</button>
                        </div>
                    </div>
                </div>
            </div>
        </header>

        <div class="info-row">OS: Ubuntu Linux // Engine: Laravel 10 // Monitor Mode: High-Precision</div>

        <div id="attack-warning-banner">[!] ALERT: CRITICAL LOAD DETECTED - POTENTIAL ATTACK [!]</div>

        <div id="system-status-bar">
            <div style="width: 10px; height: 10px; background: currentColor; border-radius: 2px;"></div>
            <span id="status-text">System Status: Optimal</span>
        </div>

        <div class="cards-grid">
            <div class="metric-card card-purple">
                <div class="card-header"><span>CPU LOAD</span> <span id="cpu-val">0</span>%</div>
                <div class="css-progress-track"><div id="cpu-bar" class="css-progress-fill"></div></div>
                <div id="cores-list" class="cores-container">
                    </div>
            </div>

            <div class="metric-card card-blue">
                <div class="card-header"><span>RAM USAGE</span> <span id="ram-val">0</span>%</div>
                <div class="css-progress-track"><div id="ram-bar" class="css-progress-fill"></div></div>
                <div style="font-size: 10px; color: var(--text-muted); display: flex; justify-content: space-between;">
                    <span>Total: <span id="ram-total">0</span>GB</span>
                    <span>Used: <span id="ram-used">0</span>GB</span>
                </div>
            </div>

            <div class="metric-card card-green">
                <div class="card-header"><span>DISK CAPACITY</span> <span id="disk-val">0</span>%</div>
                <div class="css-progress-track"><div id="disk-bar" class="css-progress-fill"></div></div>
                <div style="font-size: 10px; color: var(--text-muted);">Free Space: <span id="disk-free">0</span>GB</div>
            </div>

            <div class="metric-card card-orange">
                <div class="card-header"><span>NETWORK MB/s</span></div>
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <div>
                        <div style="color: var(--neon-orange); font-size: 18px; font-weight: bold;">↓ <span id="net-in">0.0</span></div>
                        <div style="color: var(--neon-purple); font-size: 18px; font-weight: bold;">↑ <span id="net-out">0.0</span></div>
                    </div>
                    <div style="opacity: 0.1; font-size: 30px; font-weight: 900;">NET</div>
                </div>
                <div class="css-progress-track"><div id="net-bar" class="css-progress-fill" style="width: 30%;"></div></div>
            </div>
        </div>

        <div class="bottom-section">
            <div class="chart-section">
                <div class="chart-header">SYSTEM TREND (60s)</div>
                <div style="height: 280px;"><canvas id="historyChart"></canvas></div>
            </div>

            <div class="processes-section">
                <div class="chart-header">● LIVE PROCESS MONITOR</div>
                <div class="process-table-container">
                    <table class="process-table">
                        <thead>
                            <tr><th>PID</th><th>PROCESS</th><th>CPU</th><th>RAM</th></tr>
                        </thead>
                        <tbody id="process-list"></tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="dashboard-footer">Last sync: <span id="last-update-time">--:--:--</span></div>
    </main>

    <script>
        // 1. Chart Config
        const ctx = document.getElementById('historyChart').getContext('2d');
        let timeLabels = [];
        let cpuData = [];
        let ramData = [];

        const historyChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: timeLabels,
                datasets: [
                    { label: 'CPU', data: cpuData, borderColor: '#a855f7', tension: 0.4, fill: false, borderWidth: 2 },
                    { label: 'RAM', data: ramData, borderColor: '#3b82f6', tension: 0.4, fill: false, borderWidth: 2 }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: { 
                    y: { beginAtZero: true, max: 100, grid: { color: 'rgba(255,255,255,0.05)' } },
                    x: { grid: { display: false } }
                },
                plugins: { legend: { display: false } }
            }
        });

        // 2. Fetch Data
        function updateDashboard() {
            fetch('/api/server-status')
                .then(res => res.json())
                .then(data => {
                    // Update Main Cards
                    document.getElementById('cpu-val').innerText = data.cpu_percent;
                    document.getElementById('cpu-bar').style.width = data.cpu_percent + '%';
                    document.getElementById('ram-val').innerText = data.ram_percent;
                    document.getElementById('ram-bar').style.width = data.ram_percent + '%';
                    document.getElementById('ram-total').innerText = data.ram_total;
                    document.getElementById('ram-used').innerText = data.ram_used;
                    document.getElementById('disk-val').innerText = data.disk_percent;
                    document.getElementById('disk-bar').style.width = data.disk_percent + '%';
                    document.getElementById('disk-free').innerText = data.disk_free;
                    
                    // Update Network
                    document.getElementById('net-in').innerText = data.network.in;
                    document.getElementById('net-out').innerText = data.network.out;

                    // Update Cores
                    let coresHtml = '';
                    data.cores.forEach(c => {
                        coresHtml += `
                            <div class="core-box">
                                ${c.name}
                                <div class="core-bar-mini"><div class="core-fill" style="width: ${c.val}%"></div></div>
                            </div>`;
                    });
                    document.getElementById('cores-list').innerHTML = coresHtml;

                    // Update Status & Warning
                    const warning = document.getElementById('attack-warning-banner');
                    warning.style.display = data.is_attacked ? 'block' : 'none';
                    document.getElementById('status-text').innerText = data.is_attacked ? 'ALERT: CRITICAL LOAD' : 'System Status: Optimal';

                    // Update Process List Pro
                    let procHtml = '';
                    data.processes.forEach(p => {
                        let cCol = p.cpu > 50 ? 'var(--neon-red)' : 'var(--neon-purple)';
                        let rCol = p.ram > 20 ? 'var(--neon-red)' : 'var(--neon-blue)';
                        procHtml += `
                            <tr>
                                <td style="opacity: 0.5;">#${p.pid}</td>
                                <td style="font-weight: bold;">${p.name}</td>
                                <td>
                                    <span style="color:${cCol}">${p.cpu}%</span>
                                    <div class="mini-bar-bg"><div class="mini-bar-fill" style="width:${p.cpu}%; background:${cCol}"></div></div>
                                </td>
                                <td>
                                    <span style="color:${rCol}">${p.ram}%</span>
                                    <div class="mini-bar-bg"><div class="mini-bar-fill" style="width:${p.ram}%; background:${rCol}"></div></div>
                                </td>
                            </tr>`;
                    });
                    document.getElementById('process-list').innerHTML = procHtml;

                    // Update Chart
                    let now = new Date().toLocaleTimeString();
                    document.getElementById('last-update-time').innerText = now;
                    timeLabels.push(now);
                    cpuData.push(data.cpu_percent);
                    ramData.push(data.ram_percent);
                    if (timeLabels.length > 15) { timeLabels.shift(); cpuData.shift(); ramData.shift(); }
                    historyChart.update();
                });
        }

        setInterval(updateDashboard, 5000);
        updateDashboard();

        // 3. Chat Bot Logic
        function toggleChat() {
            const win = document.getElementById('chat-window');
            win.style.display = win.style.display === 'none' ? 'flex' : 'none';
        }

        async function sendMessage() {
            const input = document.getElementById('chat-input');
            const content = document.getElementById('chat-content');
            const msg = input.value.trim();
            if(!msg) return;

            content.innerHTML += `<div style="align-self: flex-end; background: var(--neon-purple); padding: 8px; border-radius: 8px; color: white;">${msg}</div>`;
            input.value = '';

            try {
                const res = await fetch('/bot/command', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                    body: JSON.stringify({ command: msg })
                });
                const data = await res.json();
                content.innerHTML += `<div style="align-self: flex-start; background: #1f2937; padding: 8px; border-radius: 8px; border-left: 3px solid var(--neon-blue); white-space: pre-wrap;">${data.reply}</div>`;
            } catch (e) {
                content.innerHTML += `<div style="color: var(--neon-red);">⚠️ Connection Error</div>`;
            }
            content.scrollTop = content.scrollHeight;
        }
    </script>
</body>
</html>