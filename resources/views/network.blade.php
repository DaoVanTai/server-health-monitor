<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    <title>Network Center - Security System</title>
    
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

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
            --neon-cyan: #22d3ee;
            
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
            border-radius: 6px; cursor: pointer; transition: all 0.2s; display: flex; align-items: center; justify-content: center;
        }
        .btn-logout:hover { border-color: var(--border-hover); background: rgba(255,255,255,0.05); }

        .badge-secure { background: rgba(52, 211, 153, 0.1); border: 1px solid rgba(52, 211, 153, 0.2); color: var(--neon-green); padding: 6px 14px; border-radius: 9999px; font-weight: 500; font-size: 12px; display: flex; align-items: center; gap: 8px;}
        
        /* --- METRIC CARDS --- */
        .cards-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 24px; }
        .metric-card { 
            background: var(--bg-card); border-radius: 12px; padding: 24px; display: flex; flex-direction: column; 
            gap: 16px; border: 1px solid var(--border-color); transition: border-color 0.2s ease; position: relative; overflow: hidden;
        }
        .metric-card::before { content: ''; position: absolute; top: 0; left: 0; width: 100%; height: 3px; }
        .metric-card:hover { border-color: var(--border-hover); }
        
        .card-download::before { background: var(--neon-blue); }
        .card-upload::before { background: var(--neon-purple); }
        .card-status::before { background: var(--neon-green); }

        .stat-label { color: var(--text-muted); font-size: 13px; font-weight: 600; display: flex; justify-content: space-between; align-items: center; }
        .stat-value { font-size: 36px; font-weight: 700; color: var(--text-main); line-height: 1; letter-spacing: -1px; display: flex; align-items: baseline; gap: 6px; margin-top: 5px;}
        .stat-unit { font-size: 16px; color: var(--text-muted); font-weight: 500; letter-spacing: 0;}

        /* --- SECTIONS --- */
        .panel { background-color: var(--bg-card); border: 1px solid var(--border-color); border-radius: 12px; padding: 24px; display: flex; flex-direction: column; }
        .panel-header { display: flex; justify-content: space-between; align-items: center; font-size: 14px; font-weight: 600; margin-bottom: 20px; color: var(--text-main);}
        
        .detection-section { display: grid; grid-template-columns: 1fr 1.8fr; gap: 24px; }
        
        #map { height: 100%; min-height: 350px; border-radius: 8px; z-index: 1; border: 1px solid var(--border-color); background: #000;}

        /* TABLE */
        .net-table { width: 100%; border-collapse: separate; border-spacing: 0; font-size: 13px; text-align: left; }
        .net-table th { padding: 12px 8px; color: var(--text-muted); font-weight: 500; border-bottom: 1px solid var(--border-color); font-size: 12px;}
        .net-table td { padding: 12px 8px; border-bottom: 1px solid #18181b; color: var(--text-main); font-weight: 400; vertical-align: middle;}
        .net-table tr:last-child td { border-bottom: none; }
        .net-table tr:hover td { background-color: rgba(255,255,255,0.02); }

        /* SSH Warning Row */
        .row-warning td { background-color: rgba(248, 113, 113, 0.05); color: var(--text-main); }
        .row-warning td:first-child { border-left: 2px solid var(--neon-red); }

        .badge-estab { background: rgba(52, 211, 153, 0.1); color: var(--neon-green); padding: 4px 8px; border-radius: 4px; font-size: 10px; font-weight: 600; border: 1px solid rgba(52, 211, 153, 0.2);}
        .badge-ssh { background: rgba(248, 113, 113, 0.1); color: var(--neon-red); padding: 4px 8px; border-radius: 4px; font-size: 10px; font-weight: 600; border: 1px solid rgba(248, 113, 113, 0.2);}
        .text-primary { color: var(--text-main); font-weight: 600; }
        
        .dashboard-footer { text-align: right; font-size: 12px; color: var(--text-muted); font-weight: 400; margin-top: auto;}

        /* Custom Popup cho Map */
        .leaflet-popup-content-wrapper { background: var(--bg-card); color: var(--text-main); border: 1px solid var(--border-color); box-shadow: var(--shadow-soft); border-radius: 8px; font-family: 'Inter', sans-serif;}
        .leaflet-popup-tip { background: var(--border-color); }
        
        /* Nút Chặn nhanh */
        .btn-quick-block { background: transparent; color: var(--neon-red); border: 1px solid var(--neon-red); padding: 4px 10px; border-radius: 4px; font-size: 11px; font-weight: 600; cursor: pointer; transition: 0.2s;}
        .btn-quick-block:hover { background: rgba(248, 113, 113, 0.1); }

        .scrollable-area::-webkit-scrollbar { width: 4px; }
        .scrollable-area::-webkit-scrollbar-thumb { background: #3f3f46; border-radius: 4px; }
    </style>
</head>
<body>

    <aside class="sidebar">
        <div class="sidebar-logo-container" style="padding: 0 16px; margin-bottom: 24px;">
            <div style="min-width: 40px; height: 40px; border-radius: 10px; background: linear-gradient(135deg, var(--neon-cyan), var(--neon-purple)); display: flex; justify-content: center; align-items: center; font-size: 20px; color: white;"><i class="fas fa-shield-virus"></i></div>
        </div>

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
            <span>Security Audit</span>
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
                        $initials = '';
                        if (count($words) >= 2) {
                            $initials = strtoupper(substr($words[0], 0, 1) . substr(end($words), 0, 1));
                        } else {
                            $initials = strtoupper(substr($name, 0, 2));
                        }
                    @endphp
                    <div style="width: 32px; height: 32px; border-radius: 50%; border: 1px solid var(--neon-blue); display: flex; justify-content: center; align-items: center; font-weight: 700; font-size: 12px; color: var(--neon-blue); box-shadow: 0 0 10px rgba(56, 189, 248, 0.2), inset 0 0 5px rgba(56, 189, 248, 0.1);">
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
                <div class="stat-value">
                    <span id="net-in-val">0.00</span> 
                    <span class="stat-unit">MB/s</span>
                </div>
            </div>
            <div class="metric-card card-upload">
                <div class="stat-label">Current Upload <i class="fas fa-arrow-up" style="color: var(--neon-purple);"></i></div>
                <div class="stat-value">
                    <span id="net-out-val">0.00</span> 
                    <span class="stat-unit">MB/s</span>
                </div>
            </div>
            <div class="metric-card card-status">
                <div class="stat-label">Network Status <i class="fas fa-wifi" style="color: var(--neon-green);"></i></div>
                <div class="stat-value" style="color: var(--neon-green); font-size: 28px; margin-top: 14px;">
                    <i class="fas fa-check-circle" style="font-size: 20px;"></i> Connected
                </div>
            </div>
        </div>

        <div class="panel" style="height: 400px; width: 100%;">
            <div class="panel-header">
                <span><i class="fas fa-chart-line" style="color: var(--text-muted); margin-right: 8px;"></i> Bandwidth History (MB/s)</span>
                <div style="display: flex; gap: 16px; font-size: 13px; font-weight: 500;">
                    <span style="color:var(--text-muted)"><span style="background:var(--neon-blue); display:inline-block; width:8px; height:8px; border-radius:50%; margin-right:6px;"></span> Download</span> 
                    <span style="color:var(--text-muted)"><span style="background:var(--neon-purple); display:inline-block; width:8px; height:8px; border-radius:50%; margin-right:6px;"></span> Upload</span>
                </div>
            </div>
            <div style="flex: 1;"><canvas id="bandwidthChart"></canvas></div>
        </div>

        <div class="detection-section">
            <div class="panel" style="flex: 1.2;">
                <div class="panel-header">
                    <span><i class="fas fa-map-marker-alt" style="color: var(--text-muted); margin-right: 8px;"></i> Geolocation Tracking</span>
                </div>
                <div id="map"></div>
            </div>

            <div class="panel" style="flex: 1.8;">
                <div class="panel-header">
                    <span><i class="fas fa-plug" style="color: var(--text-muted); margin-right: 8px;"></i> Active Connections</span>
                    <span id="conn-count" class="badge-estab" style="background: rgba(255,255,255,0.05); color: var(--text-main); border-color: var(--border-color);">0 Active</span>
                </div>
                <div class="scrollable-area" style="overflow-y: auto; max-height: 350px; padding-right: 5px;">
                    <table class="net-table">
                        <thead style="position: sticky; top: 0; background: var(--bg-card); z-index: 10;">
                            <tr><th>Protocol</th><th>Client IP</th><th>Port</th><th>Process</th><th>Status</th><th>Action</th></tr>
                        </thead>
                        <tbody id="connection-list">
                            <tr><td colspan="6" style="text-align: center; color: var(--text-muted); padding: 30px;"><i class="fas fa-circle-notch fa-spin"></i> Initializing security scan...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="dashboard-footer">Last synced: <span id="last-update" style="color: var(--text-main);">--:--:--</span></div>
    </main>

    <script>
        Chart.defaults.color = '#a1a1aa';
        Chart.defaults.font.family = 'Inter';
        Chart.defaults.plugins.tooltip.backgroundColor = '#18181b';
        Chart.defaults.plugins.tooltip.titleColor = '#fafafa';
        Chart.defaults.plugins.tooltip.bodyColor = '#a1a1aa';
        Chart.defaults.plugins.tooltip.borderColor = '#27272a';
        Chart.defaults.plugins.tooltip.borderWidth = 1;
        Chart.defaults.plugins.tooltip.padding = 10;
        Chart.defaults.plugins.tooltip.cornerRadius = 6;

        // --- 1. BIỂU ĐỒ BĂNG THÔNG ---
        const ctx = document.getElementById('bandwidthChart').getContext('2d');
        
        let gradBlue = ctx.createLinearGradient(0, 0, 0, 300);
        gradBlue.addColorStop(0, 'rgba(56, 189, 248, 0.2)'); 
        gradBlue.addColorStop(1, 'rgba(56, 189, 248, 0)');

        let timeLabels = [], inData = [], outData = [];
        const bandwidthChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: timeLabels,
                datasets: [
                    { label: 'Download', data: inData, borderColor: '#38bdf8', backgroundColor: gradBlue, fill: true, tension: 0.4, borderWidth: 2, pointRadius: 0 },
                    { label: 'Upload', data: outData, borderColor: '#c084fc', tension: 0.4, fill: false, borderWidth: 2, pointRadius: 0 }
                ]
            },
            options: {
                responsive: true, maintainAspectRatio: false,
                scales: { 
                    y: { beginAtZero: true, border: {display: false}, grid: { color: '#18181b' } },
                    x: { border: {display: false}, grid: { display: false } }
                },
                plugins: { legend: { display: false } },
                interaction: { mode: 'index', intersect: false }
            }
        });

        // --- 2. KHỞI TẠO BẢN ĐỒ VÀ BỘ NHỚ ĐỆM GEO-IP ---
        const map = L.map('map').setView([20.0, 0.0], 2); 
        L.tileLayer('https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png', {
            attribution: '&copy; CartoDB'
        }).addTo(map);

        let markers = {}; 
        let geoCache = {};

        // --- 3. HÀM CẬP NHẬT DỮ LIỆU ---
        function updateAllData() {
            fetch('/api/server-status')
                .then(res => res.json())
                .then(data => {
                    document.getElementById('net-in-val').innerText = data.network.in;
                    document.getElementById('net-out-val').innerText = data.network.out;
                    let now = new Date().toLocaleTimeString();
                    document.getElementById('last-update').innerText = now;
                    timeLabels.push(now); inData.push(data.network.in); outData.push(data.network.out);
                    // Lưu ý: Đã tăng số lượng hiển thị lên 40 vì không gian ngang giờ đã rất rộng rãi
                    if (timeLabels.length > 40) { timeLabels.shift(); inData.shift(); outData.shift(); }
                    bandwidthChart.update();
                });

            fetch('/api/network/active-connections')
                .then(res => res.json())
                .then(data => {
                    const list = document.getElementById('connection-list');
                    document.getElementById('conn-count').innerText = `${data.length} Active`;
                    list.innerHTML = '';

                    data.forEach(conn => {
                        const isSSH = conn.local_port == '22';
                        const rowClass = isSSH ? 'row-warning' : '';
                        const ip = conn.remote_ip;
                        const safeId = ip.replace(/\./g, '-');

                        let flagHtml = `<span id="flag-${safeId}"><i class="fas fa-circle-notch fa-spin" style="font-size: 10px; color: var(--text-muted); margin-left:6px;"></i></span>`;
                        
                        if (!geoCache[ip]) {
                            if (ip === '127.0.0.1' || ip.startsWith('192.168.') || ip.startsWith('10.') || ip === '0.0.0.0') {
                                geoCache[ip] = '<span style="background: rgba(255,255,255,0.1); padding: 2px 6px; border-radius: 4px; color: #a1a1aa; font-size: 10px; font-weight: 600; margin-left: 6px;">LOCAL</span>';
                            } else {
                                geoCache[ip] = 'loading';
                                fetch(`https://get.geojs.io/v1/ip/geo/${ip}.json`)
                                    .then(r => r.json())
                                    .then(d => {
                                        if(d && d.country_code) {
                                            geoCache[ip] = `<img src="https://flagcdn.com/16x12/${d.country_code.toLowerCase()}.png" style="margin-left:8px; vertical-align: baseline; border-radius: 2px; box-shadow: 0 1px 3px rgba(0,0,0,0.5);">`;
                                        } else {
                                            geoCache[ip] = '';
                                        }
                                        let el = document.getElementById(`flag-${safeId}`);
                                        if(el) el.innerHTML = geoCache[ip];
                                    }).catch(() => { geoCache[ip] = ''; });
                            }
                        } else if (geoCache[ip] !== 'loading') {
                            flagHtml = geoCache[ip];
                        }

                        const blockForm = `
                            <form action="{{ route('firewall.block') }}" method="POST" style="margin:0;" onsubmit="return confirm('Block IP ${ip} immediately?');">
                                @csrf
                                <input type="hidden" name="ip_address" value="${ip}">
                                <input type="hidden" name="reason" value="Khóa nhanh từ Network Center">
                                <button type="submit" class="btn-quick-block" title="Block IP"><i class="fas fa-ban"></i> Block</button>
                            </form>
                        `;
                        
                        list.innerHTML += `
                            <tr class="${rowClass}">
                                <td><span style="color: var(--text-muted); font-weight: 400;">${conn.protocol}</span></td>
                                <td style="display:flex; align-items:center;"><span class="text-primary">${conn.remote_ip}</span> ${flagHtml}</td>
                                <td><span style="color: var(--neon-blue); font-weight:500;">:${conn.local_port}</span></td>
                                <td>${isSSH ? '<span class="badge-ssh"><i class="fas fa-terminal"></i> sshd</span>' : `<span style="font-weight:500;">${conn.process}</span>`}</td>
                                <td><span class="badge-estab">ESTAB</span></td>
                                <td>${(ip === '127.0.0.1' || ip.startsWith('192.168.')) ? '' : blockForm}</td>
                            </tr>
                        `;

                        if (conn.location && conn.location.lat && !markers[conn.remote_ip]) {
                            const marker = L.circleMarker([conn.location.lat, conn.location.lon], {
                                radius: 6,
                                fillColor: isSSH ? "#f87171" : "#38bdf8",
                                color: "#000",
                                weight: 2,
                                opacity: 1,
                                fillOpacity: 0.9
                            }).addTo(map);
                            marker.bindPopup(`<div style="font-weight:600; margin-bottom:4px; color: #fff;">IP: ${conn.remote_ip}</div><div style="color:var(--text-muted); font-size:12px;">${conn.location.city || 'Unknown'}, ${conn.location.country}</div>`);
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