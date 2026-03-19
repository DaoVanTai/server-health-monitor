<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    <title>Server Health Monitor - Dashboard</title>
    
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

        /* PHẦN 6H VÀ SERVICE CONTROL - GIỮ NGUYÊN KIỂU CŨ */
        .badge-live { background: rgba(245, 158, 11, 0.1); color: var(--neon-orange); padding: 4px 10px; border-radius: 4px; font-size: 10px; font-weight: bold; border: 1px solid var(--neon-orange); }
        .historical-section { background-color: var(--bg-card); border: 1px solid var(--border-color); border-radius: 12px; padding: 20px; margin: 10px 0; display: flex; gap: 20px; }
        
        .services-section { background-color: var(--bg-card); border: 1px solid var(--border-color); border-radius: 12px; padding: 20px; margin-top: 20px; display: flex; flex-direction: column; gap: 15px; }

        /* ==========================================
           KHUNG CHAT AI AEGIS (GỌN GÀNG)
           ========================================== */
        #ai-chat-box {
            position: fixed; bottom: 100px; right: 40px; width: 350px; height: 450px;
            background: #111827; border: 1px solid var(--neon-blue); border-radius: 15px;
            display: none; flex-direction: column; z-index: 9999; box-shadow: 0 10px 30px rgba(0,0,0,0.5);
        }
        #chat-header { padding: 15px; border-bottom: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center; background: rgba(59,130,246,0.1); border-radius: 15px 15px 0 0; }
        #chat-body { flex: 1; overflow-y: auto; padding: 15px; display: flex; flex-direction: column; gap: 10px; }
        .msg { max-width: 80%; padding: 10px; border-radius: 10px; font-size: 13px; line-height: 1.4; }
        .msg-ai { align-self: flex-start; background: #1f2937; color: var(--text-main); }
        .msg-user { align-self: flex-end; background: var(--neon-blue); color: white; }
        #chat-footer { padding: 15px; border-top: 1px solid var(--border-color); display: flex; gap: 10px; }
        #chat-footer input { flex: 1; background: #0b1120; border: 1px solid var(--border-color); border-radius: 5px; padding: 8px; color: white; outline: none; }
        #chat-footer button { background: var(--neon-blue); border: none; color: white; padding: 8px 15px; border-radius: 5px; cursor: pointer; }
    </style>
</head>
<body>

    <aside class="sidebar">
        <a href="{{ route('monitor') }}" class="sidebar-item active">
            <div class="sidebar-icon-wrapper"><i class="fas fa-th-large"></i></div>
            <span>Dashboard</span>
        </a>
        <a href="{{ route('network.index') }}" class="sidebar-item">
            <div class="sidebar-icon-wrapper"><i class="fas fa-network-wired"></i></div>
            <span>Network Center</span>
        </a>
    </aside>

    <main class="main-content">
        <header class="header">
            <div class="title-area">
                <h1>SYSTEM DASHBOARD</h1>
                <p>Real-time Core Resource Monitoring</p>
            </div>
            
            <div style="display: flex; gap: 15px; align-items: center;">
                <form action="{{ route('logout') }}" method="POST">
                    @csrf
                    <button type="submit" style="background: none; border: 1px solid var(--neon-red); color: var(--neon-red); padding: 5px 12px; border-radius: 4px; cursor: pointer; font-size: 11px; font-weight: bold;">Log Out</button>
                </form>
                <div style="position: relative;">
                    <button onclick="toggleAIChat()" style="background: var(--neon-blue); border: none; border-radius: 50%; width: 45px; height: 45px; cursor: pointer; box-shadow: 0 0 15px var(--neon-blue); color: white;">
                        <i class="fas fa-robot"></i>
                    </button>
                </div>
            </div>
        </header>

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
            </div>

            <div class="metric-card card-green">
                <div style="display:flex; justify-content: space-between; align-items:center">
                    <span style="font-weight:bold; font-size:12px; color:var(--text-muted)">DISK CAPACITY</span>
                    <div id="disk-status-icon"></div>
                </div>
                <div class="main-value"><span id="disk-main">0</span>%</div>
                <div class="css-progress-track"><div id="disk-bar" class="css-progress-fill" style="background: var(--neon-green)"></div></div>
            </div>
        </div>

        <div class="historical-section">
            <div style="flex: 2.5; display: flex; flex-direction: column;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                    <div style="font-weight:bold; color: var(--neon-orange); font-size: 14px; letter-spacing: 1px;">
                        <i class="fas fa-history"></i> HISTORICAL ANALYSIS (6H)
                    </div>
                    <span class="badge-live">Last 6 Hours</span>
                </div>
                <div style="flex: 1; height: 200px;"><canvas id="historicalChart"></canvas></div>
            </div>
            <div style="flex: 1; border-left: 1px solid var(--border-color); padding-left: 20px;">
                <div style="font-weight:bold; font-size: 12px; color: var(--text-muted); margin-bottom: 15px;">TOP 3 CPU SPIKES</div>
                <div id="spikes-list"></div>
            </div>
        </div>

        <div class="bottom-section">
            <div class="chart-section">
                <div style="font-weight:bold; margin-bottom: 20px;">CORE TRENDS (60s)</div>
                <div style="height: 280px;"><canvas id="realtimeChart"></canvas></div>
            </div>
            <div class="processes-section">
                <div style="font-weight:bold;">● LIVE PROCESS MONITOR</div>
                <table class="process-table"><tbody id="process-list"></tbody></table>
                
                <div class="services-section">
                    <div style="font-weight:bold; color: var(--neon-blue); font-size: 14px;"><i class="fas fa-terminal"></i> SERVICE CONTROL</div>
                    <div style="background: rgba(255,255,255,0.03); padding: 10px; border-radius: 8px;">
                        <span>Nginx: </span><span id="status-nginx"></span>
                        <button onclick="sendControl('nginx', 'restart')" style="float:right; background:none; border:1px solid var(--neon-blue); color:var(--neon-blue); font-size:10px; padding:2px 5px; cursor:pointer;">Restart</button>
                    </div>
                    <div style="background: rgba(255,255,255,0.03); padding: 10px; border-radius: 8px;">
                        <span>MySQL: </span><span id="status-mysqld"></span>
                        <button onclick="sendControl('mysqld', 'restart')" style="float:right; background:none; border:1px solid var(--neon-purple); color:var(--neon-purple); font-size:10px; padding:2px 5px; cursor:pointer;">Restart</button>
                    </div>
                </div>
            </div>
        </div>

        <div id="ai-chat-box">
            <div id="chat-header">
                <span style="font-weight: bold; color: white;"><i class="fas fa-robot"></i> Aegis AI Assistant</span>
                <i class="fas fa-times" onclick="toggleAIChat()" style="cursor: pointer;"></i>
            </div>
            <div id="chat-body">
                <div class="msg msg-ai">Chào Admin! Tôi là Aegis. Tôi đã sẵn sàng hỗ trợ bạn giám sát hệ thống.</div>
            </div>
            <div id="chat-footer">
                <input type="text" id="ai-input" placeholder="Hỏi gì đó..." onkeypress="if(event.key==='Enter') sendChat()">
                <button onclick="sendChat()"><i class="fas fa-paper-plane"></i></button>
            </div>
        </div>

        <div class="dashboard-footer">Last sync: <span id="last-update-time">--:--:--</span></div>
    </main>

    <script>
        // --- CÁC BIẾN BIỂU ĐỒ ---
        const rtCtx = document.getElementById('realtimeChart').getContext('2d');
        const hsCtx = document.getElementById('historicalChart').getContext('2d');
        let rtLabels = [], rtCpu = [], rtRam = [];

        const realtimeChart = new Chart(rtCtx, {
            type: 'line',
            data: { labels: rtLabels, datasets: [
                { label: 'CPU', data: rtCpu, borderColor: '#a855f7', tension: 0.4, pointRadius: 0 },
                { label: 'RAM', data: rtRam, borderColor: '#3b82f6', tension: 0.4, pointRadius: 0 }
            ]},
            options: { responsive: true, maintainAspectRatio: false, scales: { y: { min: 0, max: 100 }, x: { display: false } }, plugins: { legend: { display: false } } }
        });

        const historicalChart = new Chart(hsCtx, {
            type: 'line',
            data: { labels: [], datasets: [] },
            options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } } }
        });

        // --- CẬP NHẬT DỮ LIỆU ---
        function updateDashboard() {
            fetch('/api/server-status').then(res => res.json()).then(data => {
                document.getElementById('cpu-main').innerText = data.cpu_percent;
                document.getElementById('cpu-bar').style.width = data.cpu_percent + '%';
                document.getElementById('ram-main').innerText = data.ram_percent;
                document.getElementById('ram-bar').style.width = data.ram_percent + '%';
                document.getElementById('disk-main').innerText = data.disk_percent;
                document.getElementById('disk-bar').style.width = data.disk_percent + '%';

                let pHtml = '';
                data.processes.forEach(p => { pHtml += `<tr><td>#${p.pid}</td><td><b>${p.name}</b></td><td>${p.cpu}%</td><td>${p.ram}%</td></tr>`; });
                document.getElementById('process-list').innerHTML = pHtml;

                let now = new Date().toLocaleTimeString();
                rtLabels.push(now); rtCpu.push(data.cpu_percent); rtRam.push(data.ram_percent);
                if(rtLabels.length > 15) { rtLabels.shift(); rtCpu.shift(); rtRam.shift(); }
                realtimeChart.update();
                document.getElementById('last-update-time').innerText = now;
            });
        }

        function updateHistory() {
            fetch('/api/metrics/history').then(res => res.json()).then(data => {
                const labels = data.history.map(h => { let d = new Date(h.created_at); return d.getHours() + ":" + d.getMinutes(); });
                historicalChart.data.labels = labels;
                historicalChart.data.datasets = [{ label: 'CPU', data: data.history.map(h => h.cpu_percent), borderColor: '#f59e0b', fill: true, backgroundColor: 'rgba(245,158,11,0.1)', tension: 0.4 }];
                historicalChart.update();
            });
        }

        function updateServices() {
            fetch('/api/services/status').then(res => res.json()).then(data => {
                document.getElementById('status-nginx').innerHTML = data.nginx === 'running' ? '<span style="color:var(--neon-green)">● Running</span>' : '<span style="color:var(--neon-red)">● Stopped</span>';
                document.getElementById('status-mysqld').innerHTML = data.mysqld === 'running' ? '<span style="color:var(--neon-green)">● Running</span>' : '<span style="color:var(--neon-red)">● Stopped</span>';
            });
        }

        function sendControl(svc, act) {
            fetch('/api/services/control', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
                body: JSON.stringify({ service: svc, action: act })
            }).then(res => res.json()).then(d => { alert(d.message); updateServices(); });
        }

        // --- AI CHAT LOGIC ---
        function toggleAIChat() {
            const box = document.getElementById('ai-chat-box');
            box.style.display = box.style.display === 'flex' ? 'none' : 'flex';
        }

        function sendChat() {
            const input = document.getElementById('ai-input');
            const msg = input.value;
            if(!msg) return;
            appendMsg(msg, 'user');
            input.value = '';

            fetch('/api/ai/chat', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
                body: JSON.stringify({ message: msg })
            }).then(res => res.json()).then(data => {
                appendMsg(data.reply, 'ai');
            });
        }

        function appendMsg(text, sender) {
            const body = document.getElementById('chat-body');
            const div = document.createElement('div');
            div.className = `msg msg-${sender}`;
            div.innerText = text;
            body.appendChild(div);
            body.scrollTop = body.scrollHeight;
        }

        setInterval(updateDashboard, 5000); updateDashboard();
        setInterval(updateServices, 10000); updateServices();
        updateHistory();
    </script>
</body>
</html>