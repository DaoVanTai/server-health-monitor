<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SHIELD-AI SERVER MONITOR</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        :root {
            --bg-main: #0b1120; --bg-card: #111827; --border-color: #1f2937;
            --text-main: #f3f4f6; --text-muted: #9ca3af;
            --neon-purple: #a855f7; --neon-blue: #3b82f6; --neon-green: #22c55e; --neon-red: #ef4444;
        }
        body { margin: 0; padding: 0; background-color: var(--bg-main); color: var(--text-main); font-family: 'Segoe UI', Tahoma, sans-serif; display: flex; min-height: 100vh; }
        .main-content { flex: 1; padding: 30px 40px; display: flex; flex-direction: column; gap: 20px; }
        .header { display: flex; justify-content: space-between; align-items: flex-start; border-bottom: 1px solid var(--border-color); padding-bottom: 15px;}
        .title-area h1 { font-size: 24px; letter-spacing: 2px; margin: 0 0 5px 0; display: flex; align-items: center; gap: 10px;}
        .title-area p { color: var(--text-muted); margin: 0; font-size: 13px; }
        
        #system-status-bar { display: flex; gap: 15px; align-items: center; background-color: rgba(34, 197, 94, 0.1); border: 1px solid rgba(34, 197, 94, 0.3); color: var(--neon-green); padding: 12px 20px; border-radius: 6px; font-weight: bold; font-size: 14px; }

        .cards-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 25px; }
        .metric-card { background-color: var(--bg-card); border-radius: 12px; padding: 20px; display: flex; flex-direction: column; gap: 15px; transition: transform 0.3s ease; }
        .metric-card:hover { transform: translateY(-5px); }
        .card-purple { border: 1px solid var(--neon-purple); box-shadow: inset 0 0 15px rgba(168, 85, 247, 0.05); }
        .card-blue { border: 1px solid var(--neon-blue); box-shadow: inset 0 0 15px rgba(59, 130, 246, 0.05); }
        .card-green { border: 1px solid var(--neon-green); box-shadow: inset 0 0 15px rgba(34, 197, 94, 0.05); }

        .card-header { font-weight: bold; font-size: 14px; letter-spacing: 1px; display: flex; justify-content: space-between;}
        .card-content { display: flex; justify-content: space-between; align-items: center; }
        .icon-area svg { width: 60px; height: 60px; opacity: 0.8; }
        .value-area { text-align: right; }
        .main-value { font-size: 32px; font-weight: bold; margin: 0; }
        .card-purple .main-value, .card-purple svg { color: var(--neon-purple); fill: var(--neon-purple); text-shadow: 0 0 10px rgba(168, 85, 247, 0.5); }
        .card-blue .main-value, .card-blue svg { color: var(--neon-blue); fill: var(--neon-blue); text-shadow: 0 0 10px rgba(59, 130, 246, 0.5); }
        .card-green .main-value, .card-green svg { color: var(--neon-green); fill: var(--neon-green); text-shadow: 0 0 10px rgba(34, 197, 94, 0.5); }
        .sub-value { font-size: 12px; color: var(--text-muted); margin: 0; text-transform: uppercase;}

        .css-progress-track { height: 6px; background-color: rgba(255,255,255,0.1); border-radius: 4px; width: 100%; }
        .css-progress-fill { height: 100%; transition: width 0.5s ease; width: 0%; border-radius: 4px;}
        #cpu-bar { background-color: var(--neon-purple); box-shadow: 0 0 8px var(--neon-purple); }
        #ram-bar { background-color: var(--neon-blue); box-shadow: 0 0 8px var(--neon-blue); }
        #disk-bar { background-color: var(--neon-green); box-shadow: 0 0 8px var(--neon-green); }
        .card-footer { display: flex; justify-content: space-between; font-size: 12px; color: var(--text-muted); border-top: 1px solid rgba(255,255,255,0.05); padding-top: 10px; }

        /* Layout mới chia 2 cột cho phần bên dưới */
        .bottom-layout { display: grid; grid-template-columns: 2fr 1fr; gap: 25px; margin-top: 10px; }
        .chart-section { background-color: var(--bg-card); border: 1px solid var(--border-color); border-radius: 12px; padding: 20px; display: flex; flex-direction: column; min-height: 250px; }
        .chart-header { display: flex; justify-content: space-between; font-size: 14px; font-weight: bold; margin-bottom: 20px; border-bottom: 1px solid rgba(255,255,255,0.05); padding-bottom: 10px;}
        .chart-legend { display: flex; gap: 15px; font-size: 12px; }
        .chart-area { flex: 1; position: relative; }

        /* Style cho bảng Top Tiến trình */
        .process-table { width: 100%; border-collapse: collapse; font-size: 13px; text-align: left; }
        .process-table th { padding: 8px 5px; color: var(--text-muted); font-weight: normal; border-bottom: 1px solid var(--border-color); }
        .process-table td { padding: 10px 5px; border-bottom: 1px solid rgba(255,255,255,0.02); }
        .process-table tr:hover td { background-color: rgba(255,255,255,0.05); }
        .proc-name { color: var(--text-main); font-weight: bold; }
    </style>
</head>
<body>
    <main class="main-content">
        <header class="header">
            <div class="title-area">
                <h1>🛡️ SHIELD-AI SERVER MONITOR</h1>
                <p>Hệ thống giám sát và phát hiện sự cố theo thời gian thực</p>
            </div>
        </header>

        <div id="system-status-bar">
            <div style="width: 12px; height: 12px; background: currentColor; border-radius: 50%;"></div>
            <span id="status-text">Hệ thống đang hoạt động ổn định (Safe Status)</span>
        </div>

        <div class="cards-grid">
            <div class="metric-card card-purple">
                <div class="card-header">CPU METRICS</div>
                <div class="card-content">
                    <div class="icon-area"><svg viewBox="0 0 24 24"><path d="M19 5h-2V3h-2v2h-2V3h-2v2H9V3H7v2H5c-1.1 0-2 .9-2 2v2H1v2h2v2H1v2h2v2c0 1.1.9 2 2 2h2v2h2v-2h2v2h2v-2h2v2h2v-2h2c1.1 0 2-.9 2-2v-2h2v-2h-2v-2h2v-2h-2v-2h2V7h-2V5c0-1.1-.9-2-2-2zm0 14H5V5h14v14z"/><path d="M7 7h10v10H7z"/></svg></div>
                    <div class="value-area"><p class="main-value"><span id="cpu-val">0.00</span><span style="font-size: 18px;">%</span></p><p class="sub-value">CPU LOAD</p></div>
                </div>
                <div class="css-progress-track"><div id="cpu-bar" class="css-progress-fill"></div></div>
                <div class="card-footer"><div>Processor</div><div>Cores: 2</div></div>
            </div>

            <div class="metric-card card-blue">
                <div class="card-header">RAM UTILIZATION</div>
                <div class="card-content">
                    <div class="icon-area"><svg viewBox="0 0 24 24"><path d="M21 9V7c0-1.1-.9-2-2-2H5c-1.1 0-2 .9-2 2v2H1v2h2v2H1v2h2v2c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2v-2h2v-2h-2v-2h2V9h-2zm-2 8H5V7h14v10z"/><path d="M7 9h2v6H7zm4 0h2v6h-2zm4 0h2v6h-2z"/></svg></div>
                    <div class="value-area"><p class="main-value"><span id="ram-val">0.00</span><span style="font-size: 18px;">%</span></p><p class="sub-value">RAM USAGE</p></div>
                </div>
                <div class="css-progress-track"><div id="ram-bar" class="css-progress-fill"></div></div>
                <div class="card-footer"><div>Total: <span id="ram-total">0</span> GB</div><div>Used: <span id="ram-used">0</span> GB</div></div>
            </div>

            <div class="metric-card card-green">
                <div class="card-header">DISK CAPACITY</div>
                <div class="card-content">
                    <div class="icon-area"><svg viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.41 0-8-3.59-8-8s3.59-8 8-8 8 3.59 8 8-3.59 8-8 8zm0-12.5c-2.49 0-4.5 2.01-4.5 4.5s2.01 4.5 4.5 4.5 4.5-2.01 4.5-4.5-2.01-4.5-4.5-4.5zm0 5.5c-.55 0-1-.45-1-1s.45-1 1-1 1 .45 1 1-.45 1-1 1z"/></svg></div>
                    <div class="value-area"><p class="main-value"><span id="disk-val">0.00</span><span style="font-size: 18px;">%</span></p><p class="sub-value">DISK USAGE</p></div>
                </div>
                <div class="css-progress-track"><div id="disk-bar" class="css-progress-fill"></div></div>
                <div class="card-footer"><div>Storage (/)</div><div>Free: <span id="disk-free">0</span> GB</div></div>
            </div>
        </div>

        <div class="bottom-layout">
            <div class="chart-section">
                <div class="chart-header">
                    <span>CORE METRICS HISTORY (60s)</span>
                    <div class="chart-legend"><span style="color:var(--neon-purple)">● CPU</span> <span style="color:var(--neon-blue)">● RAM</span></div>
                </div>
                <div class="chart-area"><canvas id="historyChart"></canvas></div>
            </div>

            <div class="chart-section" style="min-height: auto;">
                <div class="chart-header">
                    <span><span style="color:var(--neon-blue)">●</span> TOP PROCESSES (RAM)</span>
                </div>
                <table class="process-table">
                    <thead>
                        <tr><th>PID</th><th>PROCESS</th><th>RAM %</th><th>CPU %</th></tr>
                    </thead>
                    <tbody id="process-list">
                        </tbody>
                </table>
            </div>
        </div>
    </main>

    <script>
        const ctx = document.getElementById('historyChart').getContext('2d');
        let gradPurple = ctx.createLinearGradient(0, 0, 0, 200);
        gradPurple.addColorStop(0, 'rgba(168, 85, 247, 0.3)'); gradPurple.addColorStop(1, 'rgba(168, 85, 247, 0)');
        let gradBlue = ctx.createLinearGradient(0, 0, 0, 200);
        gradBlue.addColorStop(0, 'rgba(59, 130, 246, 0.3)'); gradBlue.addColorStop(1, 'rgba(59, 130, 246, 0)');

        let timeLabels = [], cpuData = [], ramData = [];

        const chart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: timeLabels,
                datasets: [
                    { label: 'CPU', data: cpuData, borderColor: '#a855f7', backgroundColor: gradPurple, tension: 0.4, fill: true, pointRadius: 0 },
                    { label: 'RAM', data: ramData, borderColor: '#3b82f6', backgroundColor: gradBlue, tension: 0.4, fill: true, pointRadius: 0 }
                ]
            },
            options: {
                responsive: true, maintainAspectRatio: false,
                scales: {
                    y: { min: 0, max: 100, grid: { color: 'rgba(255,255,255,0.05)' }, ticks: { color: '#9ca3af' } },
                    x: { grid: { color: 'rgba(255,255,255,0.05)' }, ticks: { color: '#9ca3af' } }
                },
                plugins: { legend: { display: false } }
            }
        });

        function fetchData() {
            fetch('/api/server-status')
                .then(res => res.json())
                .then(data => {
                    // Cập nhật thông số tĩnh
                    document.getElementById('cpu-val').innerText = data.cpu_percent;
                    document.getElementById('ram-val').innerText = data.ram_percent;
                    document.getElementById('ram-total').innerText = data.ram_total;
                    document.getElementById('ram-used').innerText = data.ram_used;
                    document.getElementById('disk-val').innerText = data.disk_percent;
                    document.getElementById('disk-free').innerText = data.disk_free;

                    document.getElementById('cpu-bar').style.width = data.cpu_percent + '%';
                    document.getElementById('ram-bar').style.width = data.ram_percent + '%';
                    document.getElementById('disk-bar').style.width = data.disk_percent + '%';

                    // Cập nhật cảnh báo
                    let sb = document.getElementById('system-status-bar');
                    if(data.is_attacked) {
                        sb.style.backgroundColor = 'rgba(239,68,68,0.1)'; sb.style.borderColor = '#ef4444'; sb.style.color = '#ef4444';
                        document.getElementById('status-text').innerText = 'CẢNH BÁO: CPU QUÁ TẢI / NGHI NGỜ TẤN CÔNG!';
                    } else {
                        sb.style.backgroundColor = 'rgba(34,197,94,0.1)'; sb.style.borderColor = 'rgba(34,197,94,0.3)'; sb.style.color = '#22c55e';
                        document.getElementById('status-text').innerText = 'Hệ thống đang hoạt động ổn định (Safe Status)';
                    }

                    // Cập nhật biểu đồ
                    let now = new Date().toLocaleTimeString();
                    timeLabels.push(now); cpuData.push(data.cpu_percent); ramData.push(data.ram_percent);
                    if(timeLabels.length > 15) { timeLabels.shift(); cpuData.shift(); ramData.shift(); }
                    chart.update();

                    // Cập nhật danh sách tiến trình
                    let tbody = document.getElementById('process-list');
                    tbody.innerHTML = '';
                    if(data.processes) {
                        data.processes.forEach(proc => {
                            tbody.innerHTML += `
                                <tr>
                                    <td style="color: var(--text-muted)">#${proc.pid}</td>
                                    <td class="proc-name">${proc.name}</td>
                                    <td style="color: var(--neon-blue)">${proc.ram}%</td>
                                    <td style="color: var(--neon-purple)">${proc.cpu}%</td>
                                </tr>
                            `;
                        });
                    }
                });
        }

        fetchData();
        setInterval(fetchData, 3000); // Tốc độ làm mới: 3 giây
    </script>
</body>
</html>
