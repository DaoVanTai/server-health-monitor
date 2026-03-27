<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Firewall Management - Security Center</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --bg-main: #0b1120; 
            --bg-card: #111827; 
            --neon-red: #ef4444; 
            --neon-blue: #3b82f6;
            --neon-orange: #f59e0b;
            --text-main: #f3f4f6;
            --text-muted: #9ca3af;
            --border-color: #1f2937;
        }
        body { background: var(--bg-main); color: var(--text-main); font-family: 'Segoe UI', sans-serif; margin: 0; display: flex; min-height: 100vh; }
        
        /* --- SIDEBAR SYNC --- */
        .sidebar { 
            width: 70px; background-color: #0f172a; border-right: 1px solid var(--border-color); 
            display: flex; flex-direction: column; padding: 20px 0; transition: width 0.3s cubic-bezier(0.4, 0, 0.2, 1); 
            overflow: hidden; white-space: nowrap; position: fixed; height: 100vh; z-index: 1000; 
        }
        .sidebar:hover { width: 220px; box-shadow: 10px 0 30px rgba(0,0,0,0.5); }
        .sidebar-item { width: 100%; padding: 15px 0; display: flex; align-items: center; color: var(--text-muted); text-decoration: none; transition: all 0.2s; border-left: 3px solid transparent; }
        .sidebar-icon-wrapper { min-width: 70px; display: flex; justify-content: center; align-items: center; }
        .sidebar-item span { opacity: 0; transform: translateX(-10px); transition: all 0.3s; font-size: 14px; font-weight: 500; }
        .sidebar:hover .sidebar-item span { opacity: 1; transform: translateX(0); }
        .sidebar-item.active { color: var(--neon-red); border-left: 3px solid var(--neon-red); background: rgba(239, 68, 68, 0.05); }
        .sidebar-item:hover { color: var(--text-main); }

        /* --- CONTENT & GRID --- */
        .main-content { margin-left: 70px; padding: 40px; width: calc(100% - 70px); box-sizing: border-box; }
        h1 { color: var(--neon-red); letter-spacing: 2px; text-transform: uppercase; margin-top: 0; }
        
        /* --- THỐNG KÊ (STATS) --- */
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-bottom: 30px; }
        .stat-card { background: var(--bg-card); padding: 20px; border-radius: 12px; border: 1px solid var(--border-color); display: flex; align-items: center; gap: 20px; box-shadow: 0 4px 6px rgba(0,0,0,0.2); }
        .stat-icon { width: 50px; height: 50px; border-radius: 10px; display: flex; justify-content: center; align-items: center; font-size: 20px; }
        .bg-red { background: rgba(239, 68, 68, 0.1); color: var(--neon-red); }
        .bg-blue { background: rgba(59, 130, 246, 0.1); color: var(--neon-blue); }
        .bg-orange { background: rgba(245, 158, 11, 0.1); color: var(--neon-orange); }
        .stat-info h3 { margin: 0; font-size: 24px; color: white; }
        .stat-info p { margin: 5px 0 0 0; font-size: 13px; color: var(--text-muted); text-transform: uppercase; letter-spacing: 1px; }

        /* --- MAIN LAYOUT --- */
        .content-grid { display: grid; grid-template-columns: 2fr 1fr; gap: 30px; }
        @media (max-width: 1200px) { .content-grid { grid-template-columns: 1fr; } }
        
        .firewall-card { background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 12px; padding: 25px; }
        .card-header { border-bottom: 1px solid var(--border-color); padding-bottom: 15px; margin-bottom: 20px; font-weight: bold; color: white; display: flex; justify-content: space-between; align-items: center;}
        
        /* --- TABLE --- */
        .ip-table { width: 100%; border-collapse: collapse; }
        .ip-table th { text-align: left; color: #9ca3af; padding: 12px; border-bottom: 1px solid #1f2937; font-size: 13px; }
        .ip-table td { padding: 12px; border-bottom: 1px solid rgba(255,255,255,0.05); font-size: 14px; }
        .input-group { display: flex; gap: 10px; margin-bottom: 20px; }
        input { background: #0b1120; border: 1px solid #1f2937; color: white; padding: 10px; border-radius: 6px; flex: 1; outline: none; }
        input:focus { border-color: var(--neon-red); }
        .btn-block { background: var(--neon-red); color: white; border: none; padding: 10px 25px; border-radius: 6px; cursor: pointer; font-weight: bold; transition: 0.3s; }
        .btn-block:hover { box-shadow: 0 0 15px rgba(239,68,68,0.4); }
        .btn-unblock { background: none; border: 1px solid #22c55e; color: #22c55e; padding: 5px 10px; border-radius: 4px; cursor: pointer; transition: 0.3s; }
        .btn-unblock:hover { background: rgba(34, 197, 94, 0.1); }

        /* --- TIMELINE --- */
        .timeline { margin-left: 10px; border-left: 2px solid #1f2937; padding-left: 20px; position: relative; max-height: 500px; overflow-y: auto; padding-right: 10px;}
        .timeline::-webkit-scrollbar { width: 6px; }
        .timeline::-webkit-scrollbar-thumb { background: #374151; border-radius: 10px; }
        .timeline-item { margin-bottom: 25px; position: relative; }
        .timeline-item::before { content: ''; position: absolute; left: -27px; top: 4px; width: 12px; height: 12px; border-radius: 50%; background: var(--neon-red); box-shadow: 0 0 8px var(--neon-red); }
        .timeline-time { font-size: 12px; color: var(--text-muted); margin-bottom: 5px; display: flex; justify-content: space-between; }
        .timeline-content { background: rgba(239, 68, 68, 0.05); padding: 12px; border-radius: 6px; border: 1px solid rgba(239, 68, 68, 0.2); }
        .timeline-ip { font-family: monospace; color: white; font-size: 14px; margin-bottom: 4px; display: block;}
        .timeline-reason { font-size: 13px; color: #cbd5e1; }
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
        <a href="{{ route('firewall.index') }}" class="sidebar-item {{ Request::is('firewall*') ? 'active' : '' }}">
            <div class="sidebar-icon-wrapper">
                <i class="fas fa-shield-alt"></i> 
            </div>
            <span>Security Firewall</span>
        </a>
        <a href="{{ route('ai.index') }}" class="sidebar-item {{ Request::is('ai-intelligence*') ? 'active' : '' }}">
            <div class="sidebar-icon-wrapper">
                <i class="fas fa-brain"></i>
            </div>
            <span>Aegis Intelligence</span>
        </a>
        <a href="{{ route('logs.index') }}" class="sidebar-item {{ Request::is('analytics/logs*') ? 'active' : '' }}">
            <div class="sidebar-icon-wrapper">
                <i class="fas fa-clipboard-list"></i>
            </div>
            <span>Log & Analytics</span>
        </a>
        <a href="{{ route('ssh.tracker') }}" class="sidebar-item {{ Request::is('security/ssh-tracker*') ? 'active' : '' }}">
            <div class="sidebar-icon-wrapper">
                <i class="fas fa-user-secret"></i>
            </div>
            <span>SSH Tracker</span>
        </a>
    </aside>

    <main class="main-content">
        <h1>🛡️ Security Firewall</h1>
        <p style="color: #9ca3af; margin-bottom: 30px;">Hệ thống giám sát và phân tích hiểm họa (SIEM Module)</p>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon bg-blue"><i class="fas fa-shield-virus"></i></div>
                <div class="stat-info">
                    <h3>{{ $totalAttacks }}</h3>
                    <p>Tổng IP bị chặn</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon bg-orange"><i class="fas fa-exclamation-triangle"></i></div>
                <div class="stat-info">
                    <h3>{{ $todayAttacks }}</h3>
                    <p>Tấn công hôm nay</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon bg-red"><i class="fas fa-robot"></i></div>
                <div class="stat-info">
                    <h3>{{ $autoBanned }}</h3>
                    <p>Hệ thống tự động Ban</p>
                </div>
            </div>
        </div>

        <div class="content-grid">
            <div class="firewall-card" style="border-color: var(--neon-red);">
                <div class="card-header">
                    <span><i class="fas fa-list"></i> DANH SÁCH ĐEN (BLACKLIST)</span>
                </div>
                
                <form action="{{ route('firewall.block') }}" method="POST">
                    @csrf
                    <div class="input-group">
                        <input type="text" name="ip_address" placeholder="Nhập địa chỉ IP..." required>
                        <input type="text" name="reason" placeholder="Lý do chặn (Tùy chọn)">
                        <button type="submit" class="btn-block"><i class="fas fa-ban"></i> CHẶN IP</button>
                    </div>
                </form>

                <table class="ip-table">
                    <thead>
                        <tr>
                            <th>IP ADDRESS (Geo-Location)</th>
                            <th>REASON</th>
                            <th>ACTION</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($blacklists as $item)
                        <tr>
                            <td style="color: var(--neon-red); font-weight: bold;">
                                <span class="ip-address">{{ $item->ip_address }}</span>
                                <span class="geo-flag" style="margin-left: 10px; font-size: 13px; color: var(--text-muted); font-weight: normal;">
                                    <i class="fas fa-spinner fa-spin"></i>
                                </span>
                            </td>
                            <td>{{ $item->reason }}</td>
                            <td>
                                <form action="{{ route('firewall.unblock', $item->id) }}" method="POST" onsubmit="return confirm('Bạn có chắc muốn gỡ chặn IP này?')">
                                    @csrf
                                    <button type="submit" class="btn-unblock"><i class="fas fa-unlock"></i> Gỡ</button>
                                </form>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="3" style="text-align: center; color: var(--text-muted); padding: 40px;">Hệ thống an toàn. Chưa có IP nào bị chặn.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="firewall-card">
                <div class="card-header">
                    <span><i class="fas fa-stream"></i> TIMELINE SỰ KIỆN</span>
                </div>
                
                <div class="timeline">
                    @forelse($timelineEvents as $event)
                        <div class="timeline-item">
                            <div class="timeline-time">
                                <span>{{ \Carbon\Carbon::parse($event->created_at)->diffForHumans() }}</span>
                                <span>{{ \Carbon\Carbon::parse($event->created_at)->format('H:i') }}</span>
                            </div>
                            <div class="timeline-content">
                                <span class="timeline-ip"><i class="fas fa-crosshairs"></i> {{ $event->ip_address }}</span>
                                <span class="timeline-reason">{{ Str::limit($event->reason, 40) }}</span>
                            </div>
                        </div>
                    @empty
                        <div style="color: var(--text-muted); text-align: center; padding: 20px 0;">
                            Chưa có dữ liệu sự kiện.
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </main>

    <script>
        document.addEventListener("DOMContentLoaded", function() {
            const ipCells = document.querySelectorAll('.ip-address');
            ipCells.forEach(cell => {
                const ip = cell.innerText.trim();
                const flagSpan = cell.nextElementSibling;
                if (ip === '127.0.0.1' || ip.startsWith('192.168.') || ip.startsWith('10.')) {
                    flagSpan.innerHTML = '<i class="fas fa-network-wired" style="color: var(--text-muted);"></i> <span style="color: var(--text-muted);">Localhost</span>';
                    return;
                }
                fetch(`https://get.geojs.io/v1/ip/geo/${ip}.json`)
                    .then(response => response.json())
                    .then(data => {
                        if (data && data.country_code) {
                            const flagUrl = `https://flagcdn.com/20x15/${data.country_code.toLowerCase()}.png`;
                            flagSpan.innerHTML = `<img src="${flagUrl}" alt="${data.country}" style="vertical-align: text-bottom; border-radius: 2px; margin-right: 5px;"><span style="color: #cbd5e1;">${data.country}</span>`;
                        } else {
                            flagSpan.innerHTML = '<i class="fas fa-question-circle"></i> Unknown';
                        }
                    })
                    .catch(error => {
                        flagSpan.innerHTML = '<i class="fas fa-exclamation-triangle" style="color: #ef4444;"></i> Lỗi định vị';
                    });
            });
        });
    </script>
</body>
</html>