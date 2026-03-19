<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    <title>Server Health Monitor - AI Aegis Edition</title>
    
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

        body { margin: 0; padding: 0; background-color: var(--bg-main); color: var(--text-main); font-family: 'Segoe UI', sans-serif; display: flex; min-height: 100vh; overflow-x: hidden; }
        
        /* --- SIDEBAR --- */
        .sidebar { width: 70px; background-color: #0f172a; border-right: 1px solid var(--border-color); display: flex; flex-direction: column; padding: 20px 0; transition: width 0.3s cubic-bezier(0.4, 0, 0.2, 1); overflow: hidden; white-space: nowrap; position: fixed; height: 100vh; z-index: 1000; }
        .sidebar:hover { width: 220px; box-shadow: 10px 0 30px rgba(0,0,0,0.5); }
        .sidebar-item { width: 100%; padding: 15px 0; display: flex; align-items: center; color: var(--text-muted); text-decoration: none; transition: all 0.2s; border-left: 3px solid transparent; }
        .sidebar-icon-wrapper { min-width: 70px; display: flex; justify-content: center; align-items: center; }
        .sidebar-item span { opacity: 0; transform: translateX(-10px); transition: all 0.3s; font-size: 14px; font-weight: 500; }
        .sidebar:hover .sidebar-item span { opacity: 1; transform: translateX(0); }
        .sidebar-item.active { color: var(--neon-blue); border-left: 3px solid var(--neon-blue); background: rgba(59, 130, 246, 0.05); }

        /* --- NỘI DUNG CHÍNH --- */
        .main-content { flex: 1; margin-left: 70px; padding: 30px 40px; display: flex; flex-direction: column; gap: 20px; }
        .header { display: flex; justify-content: space-between; align-items: flex-start; }
        .title-area h1 { font-size: 24px; letter-spacing: 2px; margin: 0; text-transform: uppercase; color: var(--text-main); }
        .title-area p { color: var(--text-muted); margin: 5px 0 0 0; font-size: 13px; }
        
        /* GRID LAYOUT */
        .cards-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; }
        .metric-card { background-color: var(--bg-card); border-radius: 12px; padding: 20px; display: flex; flex-direction: column; gap: 15px; border: 1px solid var(--border-color); transition: 0.3s; }
        .metric-card:hover { transform: translateY(-5px); border-color: var(--neon-blue); }
        .main-value { font-size: 32px; font-weight: bold; margin: 0; }
        .card-purple .main-value { color: var(--neon-purple); }
        .card-blue .main-value { color: var(--neon-blue); }
        .card-green .main-value { color: var(--neon-green); }

        /* STATUS ICONS */
        .status-ok { color: var(--neon-green); filter: drop-shadow(0 0 3px var(--neon-green)); }
        .status-warning { color: var(--neon-red); filter: drop-shadow(0 0 5px var(--neon-red)); animation: pulse-warn 1s infinite; }
        @keyframes pulse-warn { 0%, 100% { opacity: 1; } 50% { opacity: 0.3; } }

        .cores-container { display: grid; grid-template-columns: repeat(4, 1fr); gap: 5px; margin-top: 10px; }
        .core-box { background: rgba(255,255,255,0.03); padding: 5px; border-radius: 4px; font-size: 9px; text-align: center; border: 1px solid rgba(255,255,255,0.05); }
        
        .css-progress-track { height: 6px; background-color: rgba(255,255,255,0.05); border-radius: 3px; overflow: hidden; }
        .css-progress-fill { height: 100%; transition: width 0.5s ease; width: 0%; }

        /* SECTIONS */
        .historical-section { background-color: var(--bg-card); border: 1px solid var(--border-color); border-radius: 12px; padding: 20px; display: flex; gap: 20px; }
        .bottom-section { display: flex; gap: 20px; }
        .chart-section { background-color: var(--bg-card); border: 1px solid var(--border-color); border-radius: 12px; padding: 20px; flex: 1.5; min-height: 350px; }
        .processes-section { background-color: var(--bg-card); border: 1px solid var(--border-color); border-radius: 12px; padding: 20px; flex: 1.2; height: 350px; display: flex; flex-direction: column; }
        .services-section { background-color: var(--bg-card); border: 1px solid var(--border-color); border-radius: 12px; padding: 20px; flex: 1; display: flex; flex-direction: column; }

        .process-table { width: 100%; border-collapse: collapse; font-size: 12px; }
        .process-table th { text-align: left; color: var(--text-muted); padding-bottom: 10px; border-bottom: 1px solid var(--border-color); }
        .process-table td { padding: 8px 0; border-bottom: 1px solid rgba(255,255,255,0.02); }

        /* AI FRIEND UI */
        #ai-friend-panel {
            position: fixed; bottom: 100px; right: 30px; width: 350px; height: 450px;
            background: rgba(17, 24, 39, 0.9); backdrop-filter: blur(15px);
            border: 1px solid var(--neon-blue); border-radius: 20px;
            display: none; flex-direction: column; z-index: 2000;
            box-shadow: 0 0 30px rgba(59, 130, 246, 0.3);
        }
        .ai-avatar {
            width: 40px; height: 40px; border-radius: 50%; background: var(--neon-blue);
            display: flex; justify-content: center; align-items: center;
            box-shadow: 0 0 10px var(--neon-blue); animation: ai-pulse 2s infinite;
        }
        @keyframes ai-pulse { 0%, 100% { transform: scale(1); } 50% { transform: scale(1.05); } }
        #chat-messages { flex: 1; overflow-y: auto; padding: 15px; display: flex; flex-direction: column; gap: 10px; scroll-behavior: smooth; }
        .msg { max-width: 80%; padding: 10px 14px; border-radius: 12px; font-size: 13px; line-height: 1.4; }
        .msg-ai { align-self: flex-start; background: #1f2937; color: white; border-bottom-left-radius: 2px; }
        .msg-user { align-self: flex-end; background: var(--neon-blue); color: white; border-bottom-right-radius: 2px; }
        .typing { font-size: 11px; color: var(--neon-blue); display: none; margin-bottom: 5px; }

        .badge-live { background: rgba(245, 158, 11, 0.1); color: var(--neon-orange); padding: 3px 8px; border-radius: 4px; font-size: 10px; border: 1px solid var(--neon-orange); }
    </style>
</head>
<body>

    <aside class="sidebar">
        <a href="#" class="sidebar-item active"><div class="sidebar-icon-wrapper"><i class="fas fa-desktop"></i></div><span>Dashboard</span></a>
        <a href="{{ route('network.index') }}" class="sidebar-item"><div class="sidebar-icon-wrapper"><i class="fas fa-network-wired"></i></div><span>Network Center</span></a>
        <a href="#" class="sidebar-item"><div class="sidebar-icon-wrapper"><i class="fas fa-user-shield"></i></div><span>Security</span></a>
    </aside>

    <main class="main-content">
        <header class="header">
            <div class="title-area">
                <h1>SYSTEM DASHBOARD</h1>
                <p>Aegis AI Monitoring System Active</p>
            </div>
            <div style="display: flex; gap: 15px; align-items: center;">
                <form action="{{ route('logout') }}" method="POST">@csrf<button type="submit" style="background: none; border: 1px solid var(--neon-red); color: var(--neon-red); padding: 5px 12px; border-radius: 4px; cursor: pointer; font-size: 11px;">Log Out</button></form>
                <button onclick="toggleAI()" style="background: var(--neon-blue); border: none; border-radius: 50%; width: 45px; height: 45px; cursor: pointer; color: white; box-shadow: 0 0 15px var(--neon-blue);"><i class="fas fa-robot"></i></button>
            </div>
        </header>

        <div id="attack-warning-banner" style="display:none; background: rgba(239,68,68,0.1); border: 1px solid var(--neon-red); color: var(--neon-red); padding: 12px; border-radius: 8px; text-align: center; font-weight: bold; font-size: 13px;">🚨 [ALERT] SYSTEM RESOURCES CRITICAL - POTENTIAL ATTACK DETECTED 🚨</div>

        <div class="cards-grid">
            <div class="metric-card card-purple">
                <div style="display:flex; justify-content: space-between;"><span style="font-size:11px; color:var(--text-muted)">CPU LOAD</span><div id="cpu-status-icon"></div></div>
                <div class="main-value"><span id="cpu-main">0</span>%</div>
                <div class="css-progress-track"><div id="cpu-bar" class="css-progress-fill" style="background: var(--neon-purple)"></div></div>
                <div id="cores-list" class="cores-container"></div>
            </div>
            <div class="metric-card card-blue">
                <div style="display:flex; justify-content: space-between;"><span style="font-size:11px; color:var(--text-muted)">RAM USAGE</span><div id="ram-status-icon"></div></div>
                <div class="main-value"><span id="ram-main">0</span>%</div>
                <div class="css-progress-track"><div id="ram-bar" class="css-progress-fill" style="background: var(--neon-blue)"></div></div>
                <div style="font-size: 10px; color: var(--text-muted); display:flex; justify-content: space-between;"><span>Total: <span id="ram-total">0</span>GB</span><span>Used: <span id="ram-used">0</span>GB</span></div>
            </div>
            <div class="metric-card card-green">
                <div style="display:flex; justify-content: space-between;"><span style="font-size:11px; color:var(--text-muted)">DISK CAPACITY</span><div id="disk-status-icon"></div></div>
                <div class="main-value"><span id="disk-main">0</span>%</div>
                <div class="css-progress-track"><div id="disk-bar" class="css-progress-fill" style="background: var(--neon-green)"></div></div>
                <div style="font-size: 10px; color: var(--text-muted);">Free: <span id="disk-free">0</span>GB</div>
            </div>
        </div>

        <div class="historical-section">
            <div style="flex: 2.5;">
                <div style="display: flex; justify-content: space-between; margin-bottom: 15px;">
                    <span style="font-weight:bold; color: var(--neon-orange); font-size: 14px;"><i class="fas fa-history"></i> 6H RESOURCE ANALYSIS</span>
                    <span class="badge-live">Live History</span>
                </div>
                <div style="height: 200px;"><canvas id="historical24hChart"></canvas></div>
            </div>
            <div style="flex: 1; border-left: 1px solid var(--border-color); padding-left: 20px;">
                <div style="font-weight:bold; font-size: 12px; color: var(--text-muted); margin-bottom: 15px;">TOP CPU SPIKES</div>
                <div id="spikes-list" style="display: flex; flex-direction: column; gap: 10px;"></div>
            </div>
        </div>

        <div class="bottom-section">
            <div class="chart-section">
                <div style="font-weight:bold; margin-bottom: 15px; font-size: 14px;">REAL-TIME TRENDS (60s)</div>
                <div style="height: 280px;"><canvas id="historyChart"></canvas></div>
            </div>
            <div class="processes-section">
                <div style="font-weight:bold; font-size: 14px; margin-bottom: 15px;">LIVE PROCESSES</div>
                <div style="overflow-y: auto; flex: 1;"><table class="process-table"><tbody id="process-list"></tbody></table></div>
            </div>
            <div class="services-section">
                <div style="font-weight:bold; color: var(--neon-blue); margin-bottom: 15px; font-size: 14px;"><i class="fas fa-terminal"></i> SERVICE CONTROL</div>
                <div style="display: flex; flex-direction: column; gap: 12px;">
                    <div style="background: rgba(255,255,255,0.03); padding: 12px; border-radius: 8px; border-left: 3px solid var(--neon-blue);">
                        <div style="display:flex; justify-content: space-between; margin-bottom: 10px;"><span style="font-size: 13px; font-weight: bold;">Nginx</span><span id="status-nginx" style="font-size: 11px;"></span></div>
                        <div style="display:flex; gap: 5px;">
                            <button onclick="sendSvc('nginx', 'restart')" style="flex:1; background:rgba(59,130,246,0.1); border:1px solid var(--neon-blue); color:var(--neon-blue); font-size:10px; padding:5px; cursor:pointer;">RESTART</button>
                            <button onclick="sendSvc('nginx', 'stop')" style="flex:1; background:rgba(239,68,68,0.1); border:1px solid var(--neon-red); color:var(--neon-red); font-size:10px; padding:5px; cursor:pointer;">STOP</button>
                        </div>
                    </div>
                    <div style="background: rgba(255,255,255,0.03); padding: 12px; border-radius: 8px; border-left: 3px solid var(--neon-purple);">
                        <div style="display:flex; justify-content: space-between; margin-bottom: 10px;"><span style="font-size: 13px; font-weight: bold;">MySQL</span><span id="status-mysqld" style="font-size: 11px;"></span></div>
                        <button onclick="sendSvc('mysqld', 'restart')" style="width:100%; background:rgba(168,85,247,0.1); border:1px solid var(--neon-purple); color:var(--neon-purple); font-size:10px; padding:5px; cursor:pointer;">RESTART DATABASE</button>
                    </div>
                </div>
            </div>
        </div>

        <div id="ai-friend-panel">
            <div style="padding: 15px; border-bottom: 1px solid rgba(255,255,255,0.05); display: flex; align-items: center; gap: 12px;">
                <div class="ai-avatar"><i class="fas fa-ghost" style="font-size: 16px;"></i></div>
                <div><div style="font-weight: bold; font-size: 14px;">Aegis AI</div><div style="font-size: 10px; color: var(--neon-green);">System Guardian Active</div></div>
                <button onclick="toggleAI()" style="margin-left: auto; background: none; border: none; color: var(--text-muted); cursor: pointer;"><i class="fas fa-times"></i></button>
            </div>
            <div id="chat-messages">
                <div class="msg msg-ai">Hệ thống xin chào! Tôi là Aegis. Tôi đã sẵn sàng phân tích dữ liệu server cho bạn.</div>
            </div>
            <div style="padding: 15px;">
                <div id="ai-typing" class="typing"><i class="fas fa-spinner fa-spin"></i> Aegis đang phân tích dữ liệu...</div>
                <div style="display: flex; gap: 8px;">
                    <input type="text" id="ai-input" placeholder="Hỏi Aegis..." style="flex:1; background:rgba(255,255,255,0.05); border:1px solid var(--border-color); border-radius:8px; padding:8px 12px; color:white; font-size:13px; outline:none;" onkeypress="if(event.key==='Enter') sendAI()">
                    <button onclick="sendAI()" style="background: var(--neon-blue); border:none; border-radius:8px; width:40px; color:white; cursor:pointer;"><i class="fas fa-paper-plane"></i></button>
                </div>
            </div>
        </div>

        <div class="dashboard-footer">Last sync: <span id="last-update-time">--:--:--</span> | Status: <span style="color:var(--neon-green)">Secure</span></div>
    </main>

    <script>
        // --- 1. REAL-TIME CHART ---
        const ctx60s = document.getElementById('historyChart').getContext('2d');
        let labels60s = [], cpu60s = [], ram60s = [];
        const chart60s = new Chart(ctx60s, {
            type: 'line',
            data: { labels: labels60s, datasets: [
                { label: 'CPU', data: cpu60s, borderColor: '#a855f7', tension: 0.4, borderWeight: 2, pointRadius: 0 },
                { label: 'RAM', data: ram60s, borderColor: '#3b82f6', tension: 0.4, borderWeight: 2, pointRadius: 0 }
            ]},
            options: { responsive: true, maintainAspectRatio: false, scales: { y: { min: 0, max: 100, ticks: { color: '#9ca3af' }, grid: { color: 'rgba(255,255,255,0.05)' } }, x: { display: false } }, plugins: { legend: { display: false } } }
        });

        // --- 2. HISTORICAL CHART ---
        const ctx6h = document.getElementById('historical24hChart').getContext('2d');
        const chart6h = new Chart(ctx6h, {
            type: 'line',
            data: { labels: [], datasets: [] },
            options: { responsive: true, maintainAspectRatio: false, scales: { y: { max: 100, ticks: { color: '#9ca3af', font: {size: 9} } }, x: { ticks: { color: '#9ca3af', font: {size: 9}, maxTicksLimit: 10 } } }, plugins: { legend: { display: false } } }
        });

        // --- 3. UPDATES ---
        function updateAll() {
            fetch('/api/server-status').then(res => res.json()).then(data => {
                // Main Metrics
                document.getElementById('cpu-main').innerText = data.cpu_percent;
                document.getElementById('cpu-bar').style.width = data.cpu_percent + '%';
                document.getElementById('ram-main').innerText = data.ram_percent;
                document.getElementById('ram-bar').style.width = data.ram_percent + '%';
                document.getElementById('ram-total').innerText = data.ram_total;
                document.getElementById('ram-used').innerText = data.ram_used;
                document.getElementById('disk-main').innerText = data.disk_percent;
                document.getElementById('disk-bar').style.width = data.disk_percent + '%';
                document.getElementById('disk-free').innerText = data.disk_free;

                // Status Icons
                const setIcon = (id, val, limit) => {
                    document.getElementById(id).innerHTML = val > limit ? 
                    `<i class="fas fa-exclamation-triangle status-warning"></i>` : 
                    `<i class="fas fa-check-circle status-ok"></i>`;
                };
                setIcon('cpu-status-icon', data.cpu_percent, 80);
                setIcon('ram-status-icon', data.ram_percent, 85);
                setIcon('disk-status-icon', data.disk_percent, 90);

                // Cores
                let cHtml = '';
                data.cores.forEach(c => { cHtml += `<div class="core-box">${c.name}<div class="css-progress-track" style="height:2px; margin-top:3px;"><div class="css-progress-fill" style="width:${c.val}%; background:var(--neon-purple)"></div></div></div>`; });
                document.getElementById('cores-list').innerHTML = cHtml;

                // Processes
                let pHtml = '';
                data.processes.forEach(p => { pHtml += `<tr><td style="color:var(--neon-blue)">#${p.pid}</td><td style="font-weight:bold">${p.name}</td><td>${p.cpu}%</td><td>${p.ram}%</td></tr>`; });
                document.getElementById('process-list').innerHTML = pHtml;

                // Banner
                document.getElementById('attack-warning-banner').style.display = data.is_attacked ? 'block' : 'none';

                // Chart Real-time
                let now = new Date().toLocaleTimeString();
                labels60s.push(now); cpu60s.push(data.cpu_percent); ram60s.push(data.ram_percent);
                if(labels60s.length > 20) { labels60s.shift(); cpu60s.shift(); ram60s.shift(); }
                chart60s.update();
                document.getElementById('last-update-time').innerText = now;
            });
        }

        function updateHistory() {
            fetch('/api/metrics/history').then(res => res.json()).then(data => {
                if(data.history.length === 0) return;
                const hLabels = data.history.map(h => { let d = new Date(h.created_at); return `${d.getHours()}:${d.getMinutes()<10?'0':''}${d.getMinutes()}`; });
                const hCpu = data.history.map(h => h.cpu_percent);
                const hRam = data.history.map(h => h.ram_percent);
                const radii = hCpu.map(v => v > 80 ? 4 : 0);
                
                chart6h.data.labels = hLabels;
                chart6h.data.datasets = [
                    { label: 'CPU', data: hCpu, borderColor: '#f59e0b', backgroundColor: 'rgba(245,158,11,0.05)', fill: true, pointRadius: radii, pointBackgroundColor: '#ef4444', tension: 0.3 },
                    { label: 'RAM', data: hRam, borderColor: '#3b82f6', tension: 0.3, pointRadius: 0 }
                ];
                chart6h.update();

                let sHtml = '';
                data.spikes.forEach(s => {
                    let d = new Date(s.created_at);
                    sHtml += `<div style="background:rgba(255,255,255,0.02); padding:8px; border-radius:6px; border-left:3px solid var(--neon-red); font-size:11px;">
                        <strong>${d.getHours()}:${d.getMinutes()<10?'0':''}${d.getMinutes()}</strong> - CPU: <span style="color:var(--neon-red)">${s.cpu_percent}%</span>
                    </div>`;
                });
                document.getElementById('spikes-list').innerHTML = sHtml || '<div style="font-size:11px; color:var(--neon-green)">No spikes detected</div>';
            });
        }

        function updateSvc() {
            fetch('/api/services/status').then(res => res.json()).then(data => {
                ['nginx', 'mysqld'].forEach(s => {
                    const el = document.getElementById('status-' + s);
                    el.innerHTML = data[s] === 'running' ? 
                    '<span style="color:var(--neon-green)">● RUNNING</span>' : 
                    '<span style="color:var(--neon-red)">● STOPPED</span>';
                });
            });
        }

        function sendSvc(s, a) {
            if(!confirm(`Xác nhận ${a} dịch vụ ${s}?`)) return;
            fetch('/api/services/control', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
                body: JSON.stringify({ service: s, action: a })
            }).then(res => res.json()).then(d => { alert(d.message); updateSvc(); });
        }

        // --- 4. AI LOGIC ---
        function toggleAI() { const p = document.getElementById('ai-friend-panel'); p.style.display = p.style.display === 'flex' ? 'none' : 'flex'; }
        
        function sendAI() {
            const i = document.getElementById('ai-input');
            const m = i.value.trim();
            if(!m) return;
            appendMsg(m, 'user'); i.value = '';
            document.getElementById('ai-typing').style.display = 'block';
            
            fetch('/api/ai/chat', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
                body: JSON.stringify({ message: m })
            }).then(res => res.json()).then(d => {
                document.getElementById('ai-typing').style.display = 'none';
                appendMsg(d.reply, 'ai');
            }).catch(() => {
                document.getElementById('ai-typing').style.display = 'none';
                appendMsg("Mất kết nối với trung tâm điều hành.", 'ai');
            });
        }

        function appendMsg(t, s) {
            const b = document.getElementById('chat-messages');
            const d = document.createElement('div');
            d.className = `msg msg-${s}`; d.innerText = t;
            b.appendChild(d); b.scrollTop = b.scrollHeight;
        }

        // INITIALIZE
        setInterval(updateAll, 5000); updateAll();
        setInterval(updateHistory, 300000); updateHistory();
        setInterval(updateSvc, 10000); updateSvc();
    </script>
</body>
</html>