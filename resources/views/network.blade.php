<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    <title>Network Center - Aegis Shield</title>
    
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Roboto+Mono:wght@400;500&display=swap" rel="stylesheet">
    
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

    <style>
        :root {
            --bg-main: #000000; --bg-card: #0a0a0a; --border-color: #27272a; --border-hover: #3f3f46;
            --text-main: #fafafa; --text-muted: #a1a1aa;
            --neon-purple: #c084fc; --neon-blue: #38bdf8; --neon-green: #34d399;
            --neon-red: #f87171; --neon-orange: #fbbf24; --neon-cyan: #22d3ee;
        }

        * { box-sizing: border-box; }
        body { margin: 0; padding: 0; background-color: var(--bg-main); color: var(--text-main); font-family: 'Inter', sans-serif; display: flex; min-height: 100vh; -webkit-font-smoothing: antialiased; }
        
        /* --- SIDEBAR THỐNG NHẤT --- */
        .sidebar { width: 72px; background-color: var(--bg-main); border-right: 1px solid var(--border-color); display: flex; flex-direction: column; padding: 20px 0; transition: width 0.2s ease; overflow: hidden; white-space: nowrap; position: fixed; height: 100vh; z-index: 1000; }
        .sidebar:hover { width: 240px; background-color: var(--bg-card); }
        .sidebar-logo-container { width: 100%; display: flex; align-items: center; padding: 0 16px; margin-bottom: 24px; padding-bottom: 24px; border-bottom: 1px solid var(--border-color); }
        .sidebar-logo { min-width: 40px; height: 40px; border-radius: 10px; background: linear-gradient(135deg, var(--neon-cyan), var(--neon-purple)); display: flex; justify-content: center; align-items: center; font-size: 20px; color: white; flex-shrink: 0; }
        .sidebar-logo-text { margin-left: 12px; opacity: 0; font-weight: 800; font-size: 16px; background: linear-gradient(135deg, #fff, #a1a1aa); -webkit-background-clip: text; -webkit-text-fill-color: transparent; transition: opacity 0.2s;}
        .sidebar:hover .sidebar-logo-text { opacity: 1; }
        
        .sidebar-item { width: 100%; padding: 16px 0; display: flex; align-items: center; color: var(--text-muted); text-decoration: none; transition: all 0.2s; }
        .sidebar-icon-wrapper { min-width: 72px; display: flex; justify-content: center; align-items: center; font-size: 1.1rem;}
        .sidebar-item span { opacity: 0; transition: opacity 0.2s; font-size: 14px; font-weight: 500;}
        .sidebar:hover .sidebar-item span { opacity: 1; }
        .sidebar-item.active { color: var(--text-main); background: rgba(255,255,255,0.03); }
        .sidebar-item:hover:not(.active) { color: var(--text-main); background: rgba(255,255,255,0.05); }

        /* --- MAIN CONTENT --- */
        .main-content { flex: 1; margin-left: 72px; padding: 40px 48px; display: flex; flex-direction: column; gap: 24px; max-width: 1600px; margin-right: auto;}
        .header { display: flex; justify-content: space-between; align-items: center; padding-bottom: 8px;}
        .title-area h1 { font-size: 24px; font-weight: 700; margin: 0; color: var(--text-main); display: flex; align-items: center; gap: 12px;}
        .title-area p { color: var(--text-muted); margin: 4px 0 0 0; font-size: 14px;}
        
        .btn-logout { background: transparent; border: 1px solid var(--border-color); color: var(--text-main); border-radius: 6px; cursor: pointer; transition: all 0.2s; display: flex; align-items: center; justify-content: center;}
        .badge-secure { background: rgba(52, 211, 153, 0.1); border: 1px solid rgba(52, 211, 153, 0.2); color: var(--neon-green); padding: 6px 14px; border-radius: 9999px; font-weight: 500; font-size: 12px; display: flex; align-items: center; gap: 8px;}

        /* --- CARDS & PANELS --- */
        .cards-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 24px; }
        .metric-card { background: var(--bg-card); border-radius: 12px; padding: 24px; border: 1px solid var(--border-color); position: relative; overflow: hidden; }
        .metric-card::before { content: ''; position: absolute; top: 0; left: 0; width: 100%; height: 3px; }
        .card-download::before { background: var(--neon-blue); }
        .card-upload::before { background: var(--neon-purple); }
        .card-status::before { background: var(--neon-green); }
        .stat-label { color: var(--text-muted); font-size: 13px; font-weight: 600; display: flex; justify-content: space-between; align-items: center; }
        .stat-value { font-size: 36px; font-weight: 700; color: var(--text-main); margin-top: 5px; display: flex; align-items: baseline; gap: 6px;}

        .panel { background-color: var(--bg-card); border: 1px solid var(--border-color); border-radius: 12px; padding: 24px; display: flex; flex-direction: column; }
        .panel-header { display: flex; justify-content: space-between; align-items: center; font-size: 14px; font-weight: 600; margin-bottom: 20px; color: var(--text-main);}
        .detection-section { display: grid; grid-template-columns: 1fr 1.8fr; gap: 24px; }
        #map { height: 100%; min-height: 350px; border-radius: 8px; border: 1px solid var(--border-color); background: #000; z-index: 1;}

        .net-table { width: 100%; border-collapse: separate; border-spacing: 0; font-size: 13px; }
        .net-table th { padding: 12px 8px; color: var(--text-muted); border-bottom: 1px solid var(--border-color); text-align: left; font-size: 11px; text-transform: uppercase;}
        .net-table td { padding: 12px 8px; border-bottom: 1px solid #18181b; color: var(--text-main); }
        .row-warning td { background-color: rgba(248, 113, 113, 0.05); }
        .badge-estab { background: rgba(52, 211, 153, 0.1); color: var(--neon-green); padding: 4px 8px; border-radius: 4px; font-size: 10px; font-weight: 600; border: 1px solid rgba(52, 211, 153, 0.2);}
        
        .btn-quick-block { background: transparent; color: var(--neon-red); border: 1px solid var(--neon-red); padding: 4px 10px; border-radius: 4px; font-size: 11px; font-weight: 600; cursor: pointer; transition: 0.2s;}
        .scrollable-area::-webkit-scrollbar { width: 4px; }
        .scrollable-area::-webkit-scrollbar-thumb { background: #3f3f46; border-radius: 4px; }
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
        <a href="{{ route('network.index') }}" class="sidebar-item active">
            <div class="sidebar-icon-wrapper"><i class="fas fa-globe"></i></div><span>Network</span>
        </a>
        <a href="{{ route('firewall.index') }}" class="sidebar-item">
            <div class="sidebar-icon-wrapper"><i class="fas fa-shield-alt"></i></div><span>Security</span>
        </a>
        <a href="{{ route('ai.index') }}" class="sidebar-item">
            <div class="sidebar-icon-wrapper"><i class="fas fa-sparkles"></i></div><span>AI Insight</span>
        </a>
        <a href="{{ route('logs.index') }}" class="sidebar-item">
            <div class="sidebar-icon-wrapper"><i class="fas fa-list-ul"></i></div><span>Logs</span>
        </a>
    </aside>

    <main class="main-content">
        <header class="header">
            <div class="title-area">
                <h1><i class="fas fa-globe" style="color: var(--neon-blue);"></i> Network Center</h1>
                <p>Advanced Traffic Monitoring & Connection Analytics</p>
            </div>
            
            <div style="display: flex; gap: 12px; align-items: center;">
                <div class="badge-secure"><i class="fas fa-shield-check"></i> Gateway Secure</div>

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
                        $initials = (count($words) >= 2) ? strtoupper(substr($words[0], 0, 1) . substr(end($words), 0, 1)) : strtoupper(substr($name, 0, 2));
                    @endphp
                    <div style="width: 32px; height: 32px; border-radius: 50%; border: 1px solid var(--neon-blue); display: flex; justify-content: center; align-items: center; font-weight: 700; font-size: 12px; color: var(--neon-blue); box-shadow: 0 0 10px rgba(56, 189, 248, 0.2);">
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

        <div class="cards-grid">
            <div class="metric-card card-download">
                <div class="stat-label">Current Download <i class="fas fa-arrow-down" style="color: var(--neon-blue);"></i></div>
                <div class="stat-value"><span id="net-in-val">0.00</span> <span style="font-size: 16px; color: var(--text-muted);">MB/s</span></div>
            </div>
            <div class="metric-card card-upload">
                <div class="stat-label">Current Upload <i class="fas fa-arrow-up" style="color: var(--neon-purple);"></i></div>
                <div class="stat-value"><span id="net-out-val">0.00</span> <span style="font-size: 16px; color: var(--text-muted);">MB/s</span></div>
            </div>
            <div class="metric-card card-status">
                <div class="stat-label">Network Status <i class="fas fa-wifi" style="color: var(--neon-green);"></i></div>
                <div class="stat-value" style="color: var(--neon-green); font-size: 24px;"><i class="fas fa-check-circle"></i> Connected</div>
            </div>
        </div>

        <div class="panel" style="height: 400px; width: 100%;">
            <div class="panel-header">
                <span><i class="fas fa-chart-line" style="color: var(--text-muted); margin-right: 8px;"></i> Bandwidth History (MB/s)</span>
                <div style="display: flex; gap: 16px; font-size: 13px;">
                    <span style="color:var(--text-muted)"><span style="background:var(--neon-blue); display:inline-block; width:8px; height:8px; border-radius:50%; margin-right:6px;"></span> Download</span> 
                    <span style="color:var(--text-muted)"><span style="background:var(--neon-purple); display:inline-block; width:8px; height:8px; border-radius:50%; margin-right:6px;"></span> Upload</span>
                </div>
            </div>
            <div style="flex: 1;"><canvas id="bandwidthChart"></canvas></div>
        </div>

        <div class="detection-section">
            <div class="panel" style="flex: 1.2;">
                <div class="panel-header"><span><i class="fas fa-map-marker-alt"></i> Geolocation Tracking</span></div>
                <div id="map"></div>
            </div>

            <div class="panel" style="flex: 1.8;">
                <div class="panel-header">
                    <span><i class="fas fa-plug"></i> Active Connections</span>
                    <span id="conn-count" class="badge-estab" style="background: rgba(255,255,255,0.05); color: var(--text-main);">0 Active</span>
                </div>
                <div class="scrollable-area" style="overflow-y: auto; max-height: 350px;">
                    <table class="net-table">
                        <thead style="position: sticky; top: 0; background: var(--bg-card); z-index: 10;">
                            <tr><th>Protocol</th><th>Client IP</th><th>Port</th><th>Process</th><th>Status</th><th>Action</th></tr>
                        </thead>
                        <tbody id="connection-list">
                            <tr><td colspan="6" style="text-align: center; padding: 30px; color: var(--text-muted);"><i class="fas fa-circle-notch fa-spin"></i> Initializing...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="dashboard-footer" style="text-align: right; font-size: 12px; color: var(--text-muted);">Last synced: <span id="last-update" style="color: var(--text-main);">--:--:--</span></div>
    </main>

    <script>
        // Cấu hình Chart.js
        Chart.defaults.color = '#a1a1aa'; Chart.defaults.font.family = 'Inter';
        const ctx = document.getElementById('bandwidthChart').getContext('2d');
        let gradBlue = ctx.createLinearGradient(0, 0, 0, 300); gradBlue.addColorStop(0, 'rgba(56, 189, 248, 0.2)'); gradBlue.addColorStop(1, 'rgba(56, 189, 248, 0)');
        let timeLabels = [], inData = [], outData = [];
        const bandwidthChart = new Chart(ctx, {
            type: 'line',
            data: { labels: timeLabels, datasets: [
                { label: 'Download', data: inData, borderColor: '#38bdf8', backgroundColor: gradBlue, fill: true, tension: 0.4, borderWidth: 2, pointRadius: 0 },
                { label: 'Upload', data: outData, borderColor: '#c084fc', tension: 0.4, fill: false, borderWidth: 2, pointRadius: 0 }
            ]},
            options: { responsive: true, maintainAspectRatio: false, scales: { y: { beginAtZero: true, grid: { color: '#18181b' } }, x: { grid: { display: false } } }, plugins: { legend: { display: false } } }
        });

        // Bản đồ
        const map = L.map('map').setView([20.0, 0.0], 2); 
        L.tileLayer('https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png', { attribution: '&copy; CartoDB' }).addTo(map);
        let markers = {}; let geoCache = {};

        function updateAllData() {
            fetch('/api/server-status').then(res => res.json()).then(data => {
                document.getElementById('net-in-val').innerText = data.network.in;
                document.getElementById('net-out-val').innerText = data.network.out;
                let now = new Date().toLocaleTimeString(); document.getElementById('last-update').innerText = now;
                timeLabels.push(now); inData.push(data.network.in); outData.push(data.network.out);
                if (timeLabels.length > 40) { timeLabels.shift(); inData.shift(); outData.shift(); }
                bandwidthChart.update();
            });

            fetch('/api/network/active-connections').then(res => res.json()).then(data => {
                const list = document.getElementById('connection-list');
                document.getElementById('conn-count').innerText = `${data.length} Active`;
                list.innerHTML = '';
                data.forEach(conn => {
                    const isSSH = conn.local_port == '22';
                    const ip = conn.remote_ip;
                    const safeId = ip.replace(/\./g, '-');
                    let flagHtml = `<span id="flag-${safeId}"><i class="fas fa-circle-notch fa-spin" style="font-size: 10px;"></i></span>`;
                    
                    if (!geoCache[ip]) {
                        if (ip.startsWith('127.') || ip.startsWith('192.168.')) geoCache[ip] = '<span style="font-size:10px; opacity:0.5">LOCAL</span>';
                        else fetch(`https://get.geojs.io/v1/ip/geo/${ip}.json`).then(r => r.json()).then(d => {
                            geoCache[ip] = d.country_code ? `<img src="https://flagcdn.com/16x12/${d.country_code.toLowerCase()}.png" style="border-radius:2px;">` : '';
                            let el = document.getElementById(`flag-${safeId}`); if(el) el.innerHTML = geoCache[ip];
                        });
                    } else flagHtml = geoCache[ip];

                    const blockBtn = (ip.startsWith('127.') || ip.startsWith('192.')) ? '' : `
                        <form action="{{ route('firewall.block') }}" method="POST" style="margin:0;">
                            @csrf <input type="hidden" name="ip_address" value="${ip}">
                            <button type="submit" class="btn-quick-block"><i class="fas fa-ban"></i></button>
                        </form>`;
                    
                    list.innerHTML += `<tr class="${isSSH ? 'row-warning' : ''}">
                        <td style="opacity:0.6">${conn.protocol}</td>
                        <td>${ip} ${flagHtml}</td>
                        <td style="color:var(--neon-blue)">:${conn.local_port}</td>
                        <td>${conn.process}</td>
                        <td><span class="badge-estab">ESTAB</span></td>
                        <td>${blockBtn}</td>
                    </tr>`;

                    if (conn.location && conn.location.lat && !markers[ip]) {
                        markers[ip] = L.circleMarker([conn.location.lat, conn.location.lon], { radius: 6, fillColor: isSSH ? "#f87171" : "#38bdf8", color: "#000", weight: 2, fillOpacity: 0.9 }).addTo(map);
                    }
                });
            });
        }
        setInterval(updateAllData, 5000); updateAllData();
    </script>
</body>
</html>