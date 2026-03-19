<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Network Center - (Server Health Monitoring & Detection System)</title>
    
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

    <style>
        :root {
            --bg-main: #0b1120;
            --bg-card: #111827;
            --border-color: #1f2937;
            --text-main: #f3f4f6;
            --text-muted: #9ca3af;
            --neon-blue: #3b82f6;
            --neon-purple: #a855f7;
            --neon-orange: #f59e0b;
            --neon-green: #22c55e;
            --neon-red: #ef4444;
        }

        body { margin: 0; padding: 0; background-color: var(--bg-main); color: var(--text-main); font-family: 'Segoe UI', sans-serif; display: flex; min-height: 100vh; }
        
        /* --- SIDEBAR --- */
        .sidebar { width: 70px; background-color: #0f172a; border-right: 1px solid var(--border-color); display: flex; flex-direction: column; padding: 20px 0; transition: width 0.3s cubic-bezier(0.4, 0, 0.2, 1); overflow: hidden; white-space: nowrap; position: fixed; height: 100vh; z-index: 1000; }
        .sidebar:hover { width: 220px; box-shadow: 10px 0 30px rgba(0,0,0,0.5); }
        .sidebar-item { width: 100%; padding: 15px 0; display: flex; align-items: center; color: var(--text-muted); text-decoration: none; transition: all 0.2s; border-left: 3px solid transparent; }
        .sidebar-icon-wrapper { min-width: 70px; display: flex; justify-content: center; align-items: center; }
        .sidebar-item span { opacity: 0; transform: translateX(-10px); transition: all 0.3s; font-size: 14px; font-weight: 500; }
        .sidebar:hover .sidebar-item span { opacity: 1; transform: translateX(0); }
        .sidebar-item.active { color: var(--neon-orange); border-left: 3px solid var(--neon-orange); background: rgba(245, 158, 11, 0.05); }

        /* --- NỘI DUNG CHÍNH --- */
        .main-content { flex: 1; margin-left: 70px; padding: 30px 40px; display: flex; flex-direction: column; gap: 20px; }
        .header { display: flex; justify-content: space-between; align-items: flex-start; }
        .title-area h1 { font-size: 28px; letter-spacing: 2px; margin: 0; text-transform: uppercase; color: var(--neon-orange); }
        .title-area p { color: var(--text-muted); margin: 5px 0 0 0; font-size: 14px; }

        .network-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; }
        .stat-card { background-color: var(--bg-card); border: 1px solid var(--border-color); border-radius: 12px; padding: 20px; border-left: 4px solid var(--neon-orange); }
        .stat-label { color: var(--text-muted); font-size: 12px; text-transform: uppercase; letter-spacing: 1px; }
        .stat-value { font-size: 32px; font-weight: bold; margin-top: 10px; color: var(--text-main); }

        .main-section { display: flex; gap: 20px; }
        .bandwidth-chart-box { background-color: var(--bg-card); border: 1px solid var(--border-color); border-radius: 12px; padding: 25px; flex: 2; height: 350px; display: flex; flex-direction: column; }
        .interface-box { background-color: var(--bg-card); border: 1px solid var(--border-color); border-radius: 12px; padding: 20px; flex: 1; }

        /* --- PHẦN MỚI: CHIA ĐÔI MAP VÀ TABLE --- */
        .detection-section { display: flex; gap: 20px; margin-top: 10px; }
        .map-box { flex: 1.2; background-color: var(--bg-card); border: 1px solid var(--border-color); border-radius: 12px; padding: 15px; min-height: 400px; }
        #map { height: 350px; border-radius: 8px; z-index: 1; }

        .connections-box { flex: 1.8; background-color: var(--bg-card); border: 1px solid var(--border-color); border-radius: 12px; padding: 20px; }
        
        .net-table { width: 100%; border-collapse: collapse; margin-top: 10px; font-size: 12px; }
        .net-table th { text-align: left; color: var(--text-muted); padding-bottom: 12px; border-bottom: 1px solid var(--border-color); text-transform: uppercase; font-size: 10px; }
        .net-table td { padding: 12px 0; border-bottom: 1px solid rgba(255,255,255,0.03); }

        .row-warning td { background: rgba(239, 68, 68, 0.05); border-left: 2px solid var(--neon-red); color: var(--neon-red); animation: pulse-red 2s infinite; }
        @keyframes pulse-red { 0% { opacity: 1; } 50% { opacity: 0.7; } 100% { opacity: 1; } }

        .badge-live { background: rgba(34, 197, 94, 0.1); color: var(--neon-green); padding: 4px 10px; border-radius: 4px; font-size: 10px; font-weight: bold; }
        .badge-ssh { background: rgba(239, 68, 68, 0.1); color: var(--neon-red); border: 1px solid var(--neon-red); padding: 2px 6px; border-radius: 4px; }
        .text-neon-blue { color: var(--neon-blue); font-weight: bold; }
        .dashboard-footer { text-align: right; font-size: 11px; color: var(--text-muted); margin-top: 10px; }

        /* Custom Popup cho Map */
        .leaflet-popup-content-wrapper { background: var(--bg-card); color: white; border: 1px solid var(--border-color); }
        .leaflet-popup-tip { background: var(--bg-card); }
    </style>
</head>
<body>

    <aside class="sidebar">
        <a href="{{ route('monitor') }}" class="sidebar-item {{ Request::is('monitor*') ? 'active' : '' }}">
            <div class="sidebar-icon-wrapper">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M3 12a9 9 0 1 1 18 0M12 7v5l3 3"></path></svg>
            </div>
            <span>Dashboard</span>
        </a>
        <a href="{{ route('network.index') }}" class="sidebar-item {{ Request::is('network*') ? 'active' : '' }}">
            <div class="sidebar-icon-wrapper">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="12" cy="12" r="10"></circle><line x1="2" y1="12" x2="22" y2="12"></line><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"></path></svg>
            </div>
            <span>Network Center</span>
        </a>
        <a href="{{ route('firewall.index') }}" class="sidebar-item {{ Request::is('firewall*') ? 'active' : '' }}">
    <div class="sidebar-icon-wrapper">
        <i class="fas fa-shield-alt"></i>
    </div>
    <span>Security</span>
</a>
<a href="{{ route('ai.index') }}" class="sidebar-item {{ Request::is('ai-intelligence*') ? 'active' : '' }}">
    <div class="sidebar-icon-wrapper">
        <i class="fas fa-brain"></i>
    </div>
    <span>AI Insight</span>
</a>
    </aside>

    <main class="main-content">
        <header class="header">
            <div class="title-area">
                <h1>NETWORK CENTER</h1>
                <p>Advanced Traffic Monitoring & Interface Analytics</p>
            </div>
            <div style="display: flex; gap: 15px; align-items: center;">
                <div class="badge-live">GATEWAY SECURE</div>
                <form action="{{ route('logout') }}" method="POST">
                    @csrf
                    <button type="submit" style="background: none; border: 1px solid var(--neon-red); color: var(--neon-red); padding: 5px 12px; border-radius: 4px; cursor: pointer; font-size: 11px; font-weight: bold;">Log Out</button>
                </form>
            </div>
        </header>

        <div class="network-grid">
            <div class="stat-card">
                <div class="stat-label">Current Download</div>
                <div class="stat-value" style="color: var(--neon-orange);">↓ <span id="net-in-val">0.00</span> <small style="font-size: 14px;">MB/s</small></div>
            </div>
            <div class="stat-card" style="border-left-color: var(--neon-purple);">
                <div class="stat-label">Current Upload</div>
                <div class="stat-value" style="color: var(--neon-purple);">↑ <span id="net-out-val">0.00</span> <small style="font-size: 14px;">MB/s</small></div>
            </div>
            <div class="stat-card" style="border-left-color: var(--neon-blue);">
                <div class="stat-label">Network Status</div>
                <div class="stat-value" style="color: var(--neon-blue); font-size: 24px;">Connected // Stable</div>
            </div>
        </div>

        <div class="main-section">
            <div class="bandwidth-chart-box">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                    <span style="font-weight: bold; letter-spacing: 1px; font-size: 14px;">BANDWIDTH HISTORY (MB/s)</span>
                </div>
                <div style="flex: 1;"><canvas id="bandwidthChart"></canvas></div>
            </div>

            <div class="interface-box">
                <div style="font-weight: bold; margin-bottom: 15px; font-size: 14px;">ACTIVE INTERFACES</div>
                <table class="net-table">
                    <thead><tr><th>IFACE</th><th>STATUS</th><th>TOTAL IN</th></tr></thead>
                    <tbody id="interface-list"></tbody>
                </table>
            </div>
        </div>

        <div class="detection-section">
            <div class="map-box">
                <div style="font-weight: bold; margin-bottom: 10px; font-size: 14px;"><i class="fas fa-map-marker-alt"></i> GEOLOCATION TRACKING</div>
                <div id="map"></div>
            </div>

            <div class="connections-box">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                    <span style="font-weight: bold; letter-spacing: 1px; font-size: 14px;"><i class="fas fa-network-wired"></i> ACTIVE CONNECTIONS</span>
                    <span id="conn-count" class="badge-live">0 Active</span>
                </div>
                <div style="overflow-y: auto; max-height: 350px;">
                    <table class="net-table">
                        <thead>
                            <tr><th>PROTO</th><th>CLIENT IP</th><th>PORT</th><th>PROCESS</th><th>STATUS</th></tr>
                        </thead>
                        <tbody id="connection-list">
                            <tr><td colspan="5" style="text-align: center; color: var(--text-muted); padding: 30px;">Initializing security scan...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="dashboard-footer">Last update: <span id="last-update">--:--:--</span></div>
    </main>

    <script>
        // --- 1. BIỂU ĐỒ BĂNG THÔNG ---
        const ctx = document.getElementById('bandwidthChart').getContext('2d');
        let timeLabels = [], inData = [], outData = [];
        const bandwidthChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: timeLabels,
                datasets: [
                    { label: 'Download', data: inData, borderColor: '#f59e0b', backgroundColor: 'rgba(245, 158, 11, 0.1)', fill: true, tension: 0.4, borderWidth: 2 },
                    { label: 'Upload', data: outData, borderColor: '#a855f7', tension: 0.4, fill: false, borderWidth: 2 }
                ]
            },
            options: {
                responsive: true, maintainAspectRatio: false,
                scales: { 
                    y: { beginAtZero: true, grid: { color: 'rgba(255,255,255,0.05)' }, ticks: { color: '#9ca3af', font: { size: 10 } } },
                    x: { grid: { display: false }, ticks: { color: '#9ca3af', font: { size: 10 } } }
                },
                plugins: { legend: { display: false } }
            }
        });

        // --- 2. KHỞI TẠO BẢN ĐỒ ---
        const map = L.map('map').setView([20.0, 0.0], 2); // Toàn cầu
        L.tileLayer('https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png', {
            attribution: '&copy; CartoDB'
        }).addTo(map);

        let markers = {}; // Lưu trữ các điểm để không bị vẽ đè

        // --- 3. HÀM CẬP NHẬT DỮ LIỆU ---
        function updateAllData() {
            // Cập nhật thông số chung
            fetch('/api/server-status')
                .then(res => res.json())
                .then(data => {
                    document.getElementById('net-in-val').innerText = data.network.in;
                    document.getElementById('net-out-val').innerText = data.network.out;
                    let now = new Date().toLocaleTimeString();
                    document.getElementById('last-update').innerText = now;
                    timeLabels.push(now); inData.push(data.network.in); outData.push(data.network.out);
                    if (timeLabels.length > 20) { timeLabels.shift(); inData.shift(); outData.shift(); }
                    bandwidthChart.update();
                    document.getElementById('interface-list').innerHTML = `<tr><td>eth0</td><td><span class="badge-live">UP</span></td><td>${data.network.in} MB</td></tr>`;
                });

            // Cập nhật kết nối và Bản đồ
            fetch('/api/network/active-connections')
                .then(res => res.json())
                .then(data => {
                    const list = document.getElementById('connection-list');
                    document.getElementById('conn-count').innerText = `${data.length} Active`;
                    list.innerHTML = '';

                    data.forEach(conn => {
                        const isSSH = conn.local_port == '22';
                        const rowClass = isSSH ? 'row-warning' : '';
                        
                        list.innerHTML += `
                            <tr class="${rowClass}">
                                <td><span style="opacity: 0.5;">${conn.protocol}</span></td>
                                <td><span class="text-neon-blue">${conn.remote_ip}</span></td>
                                <td><span style="color: var(--neon-purple)">:${conn.local_port}</span></td>
                                <td>${isSSH ? '<span class="badge-ssh">sshd</span>' : conn.process}</td>
                                <td><span class="badge-live">ESTAB</span></td>
                            </tr>
                        `;

                        // Vẽ lên bản đồ nếu có dữ liệu tọa độ (Cần Controller trả về lat, lon)
                        if (conn.location && conn.location.lat && !markers[conn.remote_ip]) {
                            const marker = L.circleMarker([conn.location.lat, conn.location.lon], {
                                radius: 6,
                                fillColor: isSSH ? "#ef4444" : "#3b82f6",
                                color: "#fff",
                                weight: 1,
                                opacity: 1,
                                fillOpacity: 0.8
                            }).addTo(map);
                            marker.bindPopup(`<b>IP: ${conn.remote_ip}</b><br>${conn.location.city || 'Unknown'}, ${conn.location.country}`);
                            markers[conn.remote_ip] = marker;
                        }
                    });
                });
        }

        setInterval(updateAllData, 5000);
        updateAllData();
    </script>
</body>
</html>