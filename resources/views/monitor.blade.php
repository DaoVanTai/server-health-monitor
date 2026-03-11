<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Server Health Monitor</title>
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

        #attack-warning-banner { display: none; background-color: rgba(239, 68, 68, 0.1); border: 1px solid var(--neon-red); color: var(--neon-red); padding: 15px 20px; border-radius: 6px; font-weight: bold; text-align: center; letter-spacing: 1px; text-transform: uppercase; animation: pulse-red 1.5s infinite; }
        @keyframes pulse-red { 0% { box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.4); } 70% { box-shadow: 0 0 0 10px rgba(239, 68, 68, 0); } 100% { box-shadow: 0 0 0 0 rgba(239, 68, 68, 0); } }
        #system-status-bar { display: flex; justify-content: flex-start; gap: 15px; align-items: center; background-color: rgba(34, 197, 94, 0.1); border: 1px solid rgba(34, 197, 94, 0.3); color: var(--neon-green); padding: 12px 20px; border-radius: 6px; font-weight: bold; font-size: 14px; transition: all 0.3s ease; }

        .cards-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 25px; }
        .metric-card { background-color: var(--bg-card); border-radius: 12px; padding: 20px; display: flex; flex-direction: column; gap: 15px; position: relative; transition: transform 0.3s ease, box-shadow 0.3s ease; cursor: pointer; }
        .metric-card:hover { transform: translateY(-8px); z-index: 10; }
        .card-purple { border: 1px solid var(--neon-purple); box-shadow: inset 0 0 10px rgba(168, 85, 247, 0.05); }
        .card-blue { border: 1px solid var(--neon-blue); box-shadow: inset 0 0 10px rgba(59, 130, 246, 0.05); }
        .card-green { border: 1px solid var(--neon-green); box-shadow: inset 0 0 10px rgba(34, 197, 94, 0.05); }

        .card-header { display: flex; justify-content: space-between; font-weight: bold; font-size: 14px; letter-spacing: 1px; }
        .card-content { display: flex; justify-content: space-between; align-items: center; }
        .graphic-text { font-size: 24px; font-weight: 900; opacity: 0.1; letter-spacing: -2px; }
        .value-area { text-align: right; }
        .main-value { font-size: 36px; font-weight: bold; margin: 0; line-height: 1.2; }
        .card-purple .main-value { color: var(--neon-purple); text-shadow: 0 0 10px rgba(168, 85, 247, 0.5); }
        .card-blue .main-value { color: var(--neon-blue); text-shadow: 0 0 10px rgba(59, 130, 246, 0.5); }
        .card-green .main-value { color: var(--neon-green); text-shadow: 0 0 10px rgba(34, 197, 94, 0.5); }
        .sub-value { font-size: 12px; color: var(--text-muted); margin: 0; }

        .css-progress-track { height: 8px; background-color: rgba(255,255,255,0.1); border-radius: 4px; overflow: hidden; width: 100%; }
        .css-progress-fill { height: 100%; transition: width 0.5s ease-in-out; width: 0%; }
        #cpu-bar { background-color: var(--neon-purple); box-shadow: 0 0 5px var(--neon-purple); }
        #ram-bar { background-color: var(--neon-blue); box-shadow: 0 0 5px var(--neon-blue); }
        #disk-bar { background-color: var(--neon-green); box-shadow: 0 0 5px var(--neon-green); }
        .card-footer { display: flex; justify-content: space-between; font-size: 11px; color: var(--text-muted); border-top: 1px solid rgba(255,255,255,0.05); padding-top: 10px; }

        .bottom-section { display: flex; gap: 25px; margin-top: 5px; }
        .chart-section { background-color: var(--bg-card); border: 1px solid var(--border-color); border-radius: 12px; padding: 20px; flex: 2; display: flex; flex-direction: column; min-height: 250px; }
        .chart-header { display: flex; justify-content: space-between; font-size: 14px; font-weight: bold; margin-bottom: 20px; }
        .chart-legend { display: flex; gap: 15px; font-size: 12px; }
        .legend-item { display: flex; align-items: center; gap: 5px; }
        .dot { width: 8px; height: 8px; border-radius: 50%; }
        .dot.purple { background-color: var(--neon-purple); box-shadow: 0 0 5px var(--neon-purple);}
        .dot.blue { background-color: var(--neon-blue); box-shadow: 0 0 5px var(--neon-blue);}
        .chart-area { flex: 1; position: relative; }
        canvas { width: 100% !important; height: 100% !important; }

        .processes-section { background-color: var(--bg-card); border: 1px solid var(--border-color); border-radius: 12px; padding: 20px; flex: 1; display: flex; flex-direction: column; }
        .process-table { width: 100%; text-align: left; font-size: 12px; border-collapse: collapse; margin-top: 10px; }
        .process-table th { color: var(--text-muted); padding-bottom: 10px; border-bottom: 1px solid var(--border-color); font-weight: normal; }
        .process-table td { padding: 12px 0; border-bottom: 1px solid rgba(255,255,255,0.02); }

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
                <p>Real-time Server Monitoring and Detection System</p>
            </div>
            
            <div class="top-right-stats">
                <div><div>CORES</div><strong style="color: white;">LIVE</strong></div>
                <div><div>NETWORK</div><strong style="color: white;">SECURE</strong></div>
                
                <div style="position: relative; text-align: left; margin-left: 10px;">
                    <button onclick="toggleChat()" style="background: var(--neon-blue); border: none; border-radius: 50%; width: 45px; height: 45px; cursor: pointer; box-shadow: 0 0 15px var(--neon-blue); color: white; font-size: 20px; display: flex; align-items: center; justify-content: center; transition: transform 0.2s;">💬</button>
                    
                    <div id="chat-window" style="position: absolute; top: 55px; right: 0; display: none; width: 350px; height: 450px; background: var(--bg-card); border: 1px solid var(--neon-blue); border-radius: 12px; flex-direction: column; overflow: hidden; box-shadow: 0 10px 25px rgba(0,0,0,0.5); z-index: 1000;">
                        <div style="background: var(--neon-blue); padding: 10px; font-weight: bold; font-size: 14px; color: white;">SYS ASSISTANT</div>
                        <div id="chat-content" style="flex: 1; padding: 15px; overflow-y: auto; font-size: 13px; display: flex; flex-direction: column; gap: 10px; scroll-behavior: smooth;">
                            <div style="background: #1f2937; padding: 10px; border-radius: 8px; align-self: flex-start; max-width: 85%; color: white;">Chào Admin! Giao diện đã hiển thị tiến trình. Hãy yêu cầu tôi 'stop attack', 'history cpu', hoặc 'history ram'.</div>
                        </div>
                        <div style="padding: 10px; border-top: 1px solid var(--border-color); display: flex; gap: 5px; background: #0f172a;">
                            <input id="chat-input" type="text" placeholder="Nhập lệnh phòng thủ/lịch sử..." style="flex: 1; background: #0b1120; border: 1px solid var(--border-color); color: white; padding: 8px; border-radius: 4px; outline: none;" onkeypress="if(event.key === 'Enter') sendMessage()">
                            <button onclick="sendMessage()" style="background: var(--neon-blue); border: none; color: white; padding: 5px 15px; border-radius: 4px; cursor: pointer; font-weight: bold;">GỬI</button>
                        </div>
                    </div>
                </div>
            </div>
        </header>

        <div class="info-row">V1.0.9 // SYS.CORE // Project: Server Health Monitoring & Detection System // Ubuntu Linux</div>

        <div id="attack-warning-banner">[!] CẢNH BÁO MỨC ĐỘ CRITICAL: HỆ THỐNG QUÁ TẢI HOẶC PHÁT HIỆN TẤN CÔNG [!]</div>

        <div id="system-status-bar">
            <div style="width: 12px; height: 12px; background: currentColor; border-radius: 2px;"></div>
            <span id="status-text">Hệ thống đang hoạt động ổn định (Safe Status)</span>
        </div>

        <div class="cards-grid">
            <div class="metric-card card-purple">
                <div class="card-header"><span>CPU METRICS</span></div>
                <div class="card-content">
                    <div class="graphic-text" style="color: var(--neon-purple);">CPU</div>
                    <div class="value-area">
                        <p class="main-value"><span id="cpu-val">0.00</span><span style="font-size: 20px;">%</span></p>
                        <p class="sub-value">CPU LOAD</p>
                    </div>
                </div>
                <div class="css-progress-track"><div id="cpu-bar" class="css-progress-fill"></div></div>
                <div class="card-footer"><div>Type: Processor</div><div style="text-align: right;">Real-time Data</div></div>
            </div>

            <div class="metric-card card-blue">
                <div class="card-header"><span>RAM UTILIZATION</span></div>
                <div class="card-content">
                    <div class="graphic-text" style="color: var(--neon-blue);">RAM</div>
                    <div class="value-area">
                        <p class="main-value"><span id="ram-val">0.00</span><span style="font-size: 20px;">%</span></p>
                        <p class="sub-value">RAM USAGE</p>
                    </div>
                </div>
                <div class="css-progress-track"><div id="ram-bar" class="css-progress-fill"></div></div>
                <div class="card-footer"><div>Total: <span id="ram-total">0</span> GB</div><div style="text-align: right;">Used: <span id="ram-used">0</span> GB</div></div>
            </div>

            <div class="metric-card card-green">
                <div class="card-header"><span>DISK CAPACITY</span></div>
                <div class="card-content">
                    <div class="graphic-text" style="color: var(--neon-green);">DSK</div>
                    <div class="value-area">
                        <p class="main-value"><span id="disk-val">0.00</span><span style="font-size: 20px;">%</span></p>
                        <p class="sub-value">DISK USAGE</p>
                    </div>
                </div>
                <div class="css-progress-track"><div id="disk-bar" class="css-progress-fill"></div></div>
                <div class="card-footer"><div>Storage (/)</div><div style="text-align: right;">Free: <span id="disk-free">0</span> GB</div></div>
            </div>
        </div>

        <div class="bottom-section">
            <div class="chart-section">
                <div class="chart-header">
                    <span>CORE METRICS HISTORY (60s)</span>
                    <div class="chart-legend">
                        <div class="legend-item"><div class="dot purple"></div> CPU</div>
                        <div class="legend-item"><div class="dot blue"></div> RAM</div>
                    </div>
                </div>
                <div class="chart-area">
                    <canvas id="historyChart"></canvas>
                </div>
            </div>

            <div class="processes-section">
                <div class="chart-header">
                    <span><span style="color: var(--neon-blue); margin-right: 5px;">●</span> TOP PROCESSES (RAM)</span>
                </div>
                <table class="process-table">
                    <thead>
                        <tr>
                            <th>PID</th>
                            <th>PROCESS</th>
                            <th>RAM %</th>
                            <th>CPU %</th>
                        </tr>
                    </thead>
                    <tbody id="process-list">
                        </tbody>
                </table>
            </div>
        </div>

        <div class="dashboard-footer">
            Dữ liệu được cập nhật tự động mỗi 5 giây.<br>
            Cập nhật lần cuối: <span id="last-update-time">--:--:--</span>
        </div>
    </main>

    <script>
        // CẤU HÌNH BIỂU ĐỒ (CHART.JS)
        const ctx = document.getElementById('historyChart').getContext('2d');
        
        let gradientPurple = ctx.createLinearGradient(0, 0, 0, 300);
        gradientPurple.addColorStop(0, 'rgba(168, 85, 247, 0.5)');
        gradientPurple.addColorStop(1, 'rgba(168, 85, 247, 0.01)');

        let gradientBlue = ctx.createLinearGradient(0, 0, 0, 300);
        gradientBlue.addColorStop(0, 'rgba(59, 130, 246, 0.5)');
        gradientBlue.addColorStop(1, 'rgba(59, 130, 246, 0.01)');

        let timeLabels = [];
        let cpuDataPoints = [];
        let ramDataPoints = [];

        const historyChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: timeLabels,
                datasets: [
                    {
                        label: 'CPU',
                        data: cpuDataPoints,
                        borderColor: '#a855f7',
                        backgroundColor: gradientPurple,
                        borderWidth: 2,
                        tension: 0.4, 
                        fill: true, 
                        pointRadius: 2,
                        pointBackgroundColor: '#a855f7'
                    },
                    {
                        label: 'RAM',
                        data: ramDataPoints,
                        borderColor: '#3b82f6',
                        backgroundColor: gradientBlue,
                        borderWidth: 2,
                        tension: 0.4,
                        fill: true,
                        pointRadius: 2,
                        pointBackgroundColor: '#3b82f6'
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                animation: { duration: 500 },
                plugins: { legend: { display: false } },
                scales: {
                    y: { beginAtZero: true, max: 100, grid: { color: 'rgba(255,255,255,0.05)' }, ticks: { color: '#9ca3af' } },
                    x: { grid: { display: false }, ticks: { color: '#9ca3af' } }
                }
            }
        });

        // HÀM LẤY DỮ LIỆU TỪ MÁY CHỦ VÀ CẬP NHẬT BẢNG TIẾN TRÌNH
        function fetchRealServerData() {
            fetch('/api/server-status')
                .then(response => response.json())
                .then(data => {
                    document.getElementById('cpu-val').innerText = data.cpu_percent;
                    document.getElementById('ram-val').innerText = data.ram_percent;
                    document.getElementById('ram-total').innerText = data.ram_total;
                    document.getElementById('ram-used').innerText = data.ram_used;
                    document.getElementById('disk-val').innerText = data.disk_percent;
                    document.getElementById('disk-free').innerText = data.disk_free;

                    document.getElementById('cpu-bar').style.width = data.cpu_percent + '%';
                    document.getElementById('ram-bar').style.width = data.ram_percent + '%';
                    document.getElementById('disk-bar').style.width = data.disk_percent + '%';

                    // Cập nhật Cảnh báo
                    let warningBanner = document.getElementById('attack-warning-banner');
                    let statusBar = document.getElementById('system-status-bar');
                    let statusText = document.getElementById('status-text');

                    if (data.is_attacked) {
                        warningBanner.style.display = 'block';
                        statusBar.style.backgroundColor = 'rgba(239, 68, 68, 0.1)';
                        statusBar.style.borderColor = 'var(--neon-red)';
                        statusBar.style.color = 'var(--neon-red)';
                        statusText.innerText = 'CẢNH BÁO: HỆ THỐNG QUÁ TẢI HOẶC ĐANG BỊ TẤN CÔNG!';
                    } else {
                        warningBanner.style.display = 'none';
                        statusBar.style.backgroundColor = 'rgba(34, 197, 94, 0.1)';
                        statusBar.style.borderColor = 'rgba(34, 197, 94, 0.3)';
                        statusBar.style.color = 'var(--neon-green)';
                        statusText.innerText = 'Hệ thống đang hoạt động ổn định (Safe Status)';
                    }

                    // Cập nhật Bảng Tiến Trình (TOP PROCESSES)
                    if (data.processes && data.processes.length > 0) {
                        let html = '';
                        data.processes.forEach(p => {
                            html += `<tr>
                                <td style="color: var(--text-muted);">#${p.pid}</td>
                                <td style="font-weight: bold;">${p.name}</td>
                                <td style="color: var(--neon-blue);">${p.ram}%</td>
                                <td style="color: var(--neon-purple);">${p.cpu}%</td>
                            </tr>`;
                        });
                        document.getElementById('process-list').innerHTML = html;
                    }

                    // Cập nhật Biểu đồ
                    let now = new Date();
                    let timeString = now.toLocaleTimeString();
                    document.getElementById('last-update-time').innerText = timeString;

                    timeLabels.push(timeString);
                    cpuDataPoints.push(data.cpu_percent);
                    ramDataPoints.push(data.ram_percent);

                    if (timeLabels.length > 12) {
                        timeLabels.shift();
                        cpuDataPoints.shift();
                        ramDataPoints.shift();
                    }
                    historyChart.update();
                })
                .catch(error => {
                    console.error('Lỗi khi lấy dữ liệu server:', error);
                });
        }

        fetchRealServerData();
        setInterval(fetchRealServerData, 5000);

        // LOGIC CHAT BOT PHÒNG THỦ & LỊCH SỬ
        function toggleChat() {
            const win = document.getElementById('chat-window');
            win.style.display = win.style.display === 'none' ? 'flex' : 'none';
        }

        async function sendMessage() {
            const input = document.getElementById('chat-input');
            const content = document.getElementById('chat-content');
            const userMsg = input.value.trim();
            if (!userMsg) return;

            content.innerHTML += `<div style="background: var(--neon-purple); padding: 10px; border-radius: 8px; align-self: flex-end; max-width: 85%; font-weight: 500; color: white;">${userMsg}</div>`;
            input.value = '';
            content.scrollTop = content.scrollHeight;

            try {
                const response = await fetch('/bot/command', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({ command: userMsg })
                });

                const data = await response.json();
                content.innerHTML += `<div style="background: #1f2937; padding: 10px; border-radius: 8px; align-self: flex-start; max-width: 85%; white-space: pre-wrap; font-family: monospace; border-left: 3px solid var(--neon-blue); color: white;">${data.reply}</div>`;
            } catch (error) {
                content.innerHTML += `<div style="background: rgba(239, 68, 68, 0.2); color: var(--neon-red); padding: 10px; border-radius: 8px; align-self: flex-start;">⚠️ Lỗi: Không thể kết nối tới máy chủ!</div>`;
            }
            content.scrollTop = content.scrollHeight;
        }
    </script>
</body>
</html>