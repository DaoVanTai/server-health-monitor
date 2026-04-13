<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Firewall Management - Security Center</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Roboto:wght@400;500&display=swap" rel="stylesheet">
    
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
            padding: 8px 16px; border-radius: 6px; cursor: pointer; font-size: 13px; font-weight: 500;
            transition: all 0.2s; display: flex; align-items: center; gap: 8px;
        }
        .btn-logout:hover { border-color: var(--border-hover); background: rgba(255,255,255,0.05); }

        .badge-secure { background: rgba(248, 113, 113, 0.1); border: 1px solid rgba(248, 113, 113, 0.2); color: var(--neon-red); padding: 6px 12px; border-radius: 9999px; font-weight: 500; font-size: 12px; display: flex; align-items: center; gap: 8px;}
        .status-pulse { width: 8px; height: 8px; background: currentColor; border-radius: 50%; box-shadow: 0 0 8px currentColor; animation: pulse 2s infinite; }
        @keyframes pulse { 0% { opacity: 1; } 50% { opacity: 0.4; } 100% { opacity: 1; } }

        /* --- METRIC CARDS --- */
        .cards-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 24px; }
        .metric-card { 
            background: var(--bg-card); border-radius: 12px; padding: 24px; display: flex; flex-direction: column; 
            gap: 12px; border: 1px solid var(--border-color); transition: border-color 0.2s ease; position: relative; overflow: hidden;
        }
        .metric-card::before { content: ''; position: absolute; top: 0; left: 0; width: 100%; height: 3px; }
        .metric-card:hover { border-color: var(--border-hover); }
        
        .card-blue::before { background: var(--neon-blue); }
        .card-orange::before { background: var(--neon-orange); }
        .card-red::before { background: var(--neon-red); }

        .card-header { font-weight: 500; font-size: 13px; color: var(--text-muted); display: flex; justify-content: space-between; align-items: center;}
        .main-value { font-size: 36px; font-weight: 700; margin: 0; letter-spacing: -1px; color: var(--text-main); line-height: 1; margin-top: 8px;}

        /* --- SECTIONS --- */
        .content-grid { display: grid; grid-template-columns: 2fr 1fr; gap: 24px; }
        @media (max-width: 1200px) { .content-grid { grid-template-columns: 1fr; } }
        
        .panel { background-color: var(--bg-card); border: 1px solid var(--border-color); border-radius: 12px; padding: 24px; display: flex; flex-direction: column; }
        .panel-header { display: flex; justify-content: space-between; align-items: center; font-size: 15px; font-weight: 600; margin-bottom: 24px; color: var(--text-main);}

        /* --- FORMS & BUTTONS --- */
        .input-group { display: flex; gap: 12px; margin-bottom: 24px; }
        .input-group input { 
            background: #000000; border: 1px solid var(--border-color); color: var(--text-main); 
            padding: 12px 16px; border-radius: 8px; flex: 1; outline: none; transition: 0.2s; font-size: 13px;
        }
        .input-group input::placeholder { color: #52525b; }
        .input-group input:focus { border-color: var(--neon-red); box-shadow: 0 0 0 1px rgba(248, 113, 113, 0.2); }
        
        .btn-block { 
            background: rgba(248, 113, 113, 0.1); color: var(--neon-red); border: 1px solid rgba(248, 113, 113, 0.2); 
            padding: 12px 24px; border-radius: 8px; cursor: pointer; font-weight: 600; transition: 0.2s; white-space: nowrap; font-size: 13px;
        }
        .btn-block:hover { background: rgba(248, 113, 113, 0.2); border-color: var(--neon-red); }

        .btn-unblock { 
            background: transparent; border: 1px solid var(--border-color); color: var(--text-muted); 
            padding: 6px 12px; border-radius: 6px; cursor: pointer; transition: 0.2s; font-size: 12px; font-weight: 500;
        }
        .btn-unblock:hover { border-color: var(--neon-green); color: var(--neon-green); background: rgba(52, 211, 153, 0.05); }

        /* --- TABLE --- */
        .ip-table { width: 100%; border-collapse: separate; border-spacing: 0; font-size: 13px; text-align: left; }
        .ip-table th { padding: 12px 8px; color: var(--text-muted); font-weight: 500; border-bottom: 1px solid var(--border-color); font-size: 12px; text-transform: uppercase;}
        .ip-table td { padding: 16px 8px; border-bottom: 1px solid #18181b; color: var(--text-main); font-weight: 400; vertical-align: middle;}
        .ip-table tr:last-child td { border-bottom: none; }
        .ip-table tr:hover td { background-color: rgba(255,255,255,0.02); }

        .badge-blocked { background: rgba(248, 113, 113, 0.1); color: var(--neon-red); padding: 4px 8px; border-radius: 4px; font-size: 11px; font-weight: 600; border: 1px solid rgba(248, 113, 113, 0.2); display: inline-flex; align-items: center; gap: 4px;}

        /* --- TIMELINE --- */
        .timeline { margin-left: 8px; border-left: 1px solid var(--border-color); padding-left: 24px; position: relative; max-height: 500px; overflow-y: auto; padding-right: 10px;}
        .timeline-item { margin-bottom: 24px; position: relative; }
        .timeline-item::before { 
            content: ''; position: absolute; left: -30px; top: 4px; width: 10px; height: 10px; 
            border-radius: 50%; background: var(--bg-main); border: 2px solid var(--neon-red); box-shadow: 0 0 8px rgba(248, 113, 113, 0.4); 
        }
        .timeline-time { font-size: 12px; color: var(--text-muted); margin-bottom: 8px; display: flex; justify-content: space-between; }
        .timeline-content { background: rgba(255,255,255,0.02); padding: 16px; border-radius: 8px; border: 1px solid var(--border-color); transition: border-color 0.2s; }
        .timeline-item:hover .timeline-content { border-color: var(--border-hover); }
        .timeline-ip { font-family: 'Roboto', monospace; color: var(--neon-red); font-size: 13px; margin-bottom: 6px; display: block; font-weight: 600;}
        .timeline-reason { font-size: 13px; color: var(--text-main); line-height: 1.5; }

        /* Scrollbar */
        .scrollable-area::-webkit-scrollbar, .timeline::-webkit-scrollbar { width: 4px; }
        .scrollable-area::-webkit-scrollbar-thumb, .timeline::-webkit-scrollbar-thumb { background: #3f3f46; border-radius: 4px; }
        
        .dashboard-footer { text-align: right; font-size: 12px; color: var(--text-muted); margin-top: auto; padding-top: 20px;}
    </style>
</head>
<body>

    <aside class="sidebar">
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
            <span>Logs</span>
        </a>
        <a href="{{ route('ssh.tracker') }}" class="sidebar-item {{ Request::is('security/ssh-tracker*') ? 'active' : '' }}">
            <div class="sidebar-icon-wrapper"><i class="fas fa-user-secret"></i></div>
            <span>SSH Tracker</span>
        </a>
    </aside>

    <main class="main-content">
        <header class="header">
            <div class="title-area">
                <h1>Security Firewall</h1>
                <p>Threat Monitoring & Access Control (SIEM)</p>
            </div>
            
            <div style="display: flex; gap: 16px; align-items: center;">
                <div class="badge-secure">
                    <div class="status-pulse"></div> Firewall Active
                </div>
                <form action="{{ route('logout') }}" method="POST" style="margin: 0;">
                    @csrf
                    <button type="submit" class="btn-logout"><i class="fas fa-sign-out-alt"></i> Sign Out</button>
                </form>
            </div>
        </header>

        <div class="cards-grid">
            <div class="metric-card card-blue">
                <div class="card-header">
                    <span>Total Blocked IPs</span>
                    <i class="fas fa-shield-virus" style="color:var(--neon-blue); font-size:16px;"></i>
                </div>
                <div class="main-value">{{ $totalAttacks }}</div>
            </div>
            <div class="metric-card card-orange">
                <div class="card-header">
                    <span>Attacks Today</span>
                    <i class="fas fa-exclamation-triangle" style="color:var(--neon-orange); font-size:16px;"></i>
                </div>
                <div class="main-value">{{ $todayAttacks }}</div>
            </div>
            <div class="metric-card card-red">
                <div class="card-header">
                    <span>System Auto-Banned</span>
                    <i class="fas fa-robot" style="color:var(--neon-red); font-size:16px;"></i>
                </div>
                <div class="main-value">{{ $autoBanned }}</div>
            </div>
        </div>

        <div class="content-grid">
            <div class="panel">
                <div class="panel-header">
                    <span><i class="fas fa-list" style="margin-right:8px; color:var(--text-muted)"></i> Access Control List (Blacklist)</span>
                </div>
                
                <form action="{{ route('firewall.block') }}" method="POST">
                    @csrf
                    <div class="input-group">
                        <input type="text" name="ip_address" placeholder="Enter IP address to block..." required>
                        <input type="text" name="reason" placeholder="Reason (Optional)">
                        <button type="submit" class="btn-block"><i class="fas fa-ban" style="margin-right: 6px;"></i> BLOCK IP</button>
                    </div>
                </form>

                <div class="scrollable-area" style="overflow-y: auto; max-height: 400px; padding-right: 5px;">
                    <table class="ip-table">
                        <thead>
                            <tr>
                                <th>IP Address</th>
                                <th>Reason</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($blacklists as $item)
                            <tr>
                                <td>
                                    <div style="display:flex; align-items:center; gap: 10px;">
                                        <span class="ip-address" style="color: var(--text-main); font-weight: 600; font-family: 'Roboto', monospace;">{{ $item->ip_address }}</span>
                                        <span class="geo-flag" style="font-size: 13px; color: var(--text-muted);">
                                            <i class="fas fa-circle-notch fa-spin" style="font-size:10px;"></i>
                                        </span>
                                    </div>
                                </td>
                                <td style="color: var(--text-muted);">{{ $item->reason }}</td>
                                <td><span class="badge-blocked"><i class="fas fa-lock" style="font-size:8px;"></i> Blocked</span></td>
                                <td>
                                    <form action="{{ route('firewall.unblock', $item->id) }}" method="POST" onsubmit="return confirm('Bạn có chắc muốn gỡ chặn IP này?')">
                                        @csrf
                                        <button type="submit" class="btn-unblock"><i class="fas fa-unlock-alt"></i> Unblock</button>
                                    </form>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="4" style="text-align: center; color: var(--text-muted); padding: 60px 20px;">
                                    <i class="fas fa-shield-check" style="font-size: 24px; margin-bottom: 10px; display:block; color: var(--neon-green)"></i>
                                    System is safe. No blocked IPs found.
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="panel">
                <div class="panel-header">
                    <span><i class="fas fa-stream" style="margin-right:8px; color:var(--text-muted)"></i> Event Timeline</span>
                </div>
                
                <div class="timeline">
                    @forelse($timelineEvents as $event)
                        <div class="timeline-item">
                            <div class="timeline-time">
                                <span style="font-weight: 500; color: var(--text-main);">{{ \Carbon\Carbon::parse($event->created_at)->diffForHumans() }}</span>
                                <span>{{ \Carbon\Carbon::parse($event->created_at)->format('H:i') }}</span>
                            </div>
                            <div class="timeline-content">
                                <span class="timeline-ip"><i class="fas fa-crosshairs" style="margin-right:4px;"></i> Target: {{ $event->ip_address }}</span>
                                <span class="timeline-reason">{{ Str::limit($event->reason, 50) }}</span>
                            </div>
                        </div>
                    @empty
                        <div style="color: var(--text-muted); text-align: center; padding: 40px 0;">
                            No events recorded.
                        </div>
                    @endforelse
                </div>
            </div>
        </div>

        <div class="dashboard-footer">Protected by Aegis Firewall Engine</div>
    </main>

    <script>
        document.addEventListener("DOMContentLoaded", function() {
            const ipCells = document.querySelectorAll('.ip-address');

            ipCells.forEach(cell => {
                const ip = cell.innerText.trim();
                const flagSpan = cell.nextElementSibling; 

                // Local IP handling
                if (ip === '127.0.0.1' || ip.startsWith('192.168.') || ip.startsWith('10.')) {
                    flagSpan.innerHTML = '<span style="background: rgba(255,255,255,0.1); padding: 2px 6px; border-radius: 4px; color: #a1a1aa; font-size: 10px; font-weight: 600;">LOCAL</span>';
                    return;
                }

                // GeoIP Fetch
                fetch(`https://get.geojs.io/v1/ip/geo/${ip}.json`)
                    .then(response => response.json())
                    .then(data => {
                        if (data && data.country_code) {
                            const flagUrl = `https://flagcdn.com/16x12/${data.country_code.toLowerCase()}.png`;
                            flagSpan.innerHTML = `
                                <img src="${flagUrl}" alt="${data.country}" style="vertical-align: baseline; border-radius: 2px; box-shadow: 0 1px 3px rgba(0,0,0,0.5); margin-right:6px;">
                                <span>${data.country}</span>
                            `;
                        } else {
                            flagSpan.innerHTML = '<i class="fas fa-question-circle"></i> Unknown';
                        }
                    })
                    .catch(error => {
                        flagSpan.innerHTML = '';
                    });
            });
        });
    </script>
</body>
</html>