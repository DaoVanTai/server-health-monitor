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
            --text-main: #f3f4f6;
            --text-muted: #9ca3af;
            --border-color: #1f2937;
        }
        body { background: var(--bg-main); color: var(--text-main); font-family: 'Segoe UI', sans-serif; margin: 0; display: flex; min-height: 100vh; }
        
        /* --- SIDEBAR SYNC --- */
        .sidebar { 
            width: 70px; 
            background-color: #0f172a; 
            border-right: 1px solid var(--border-color); 
            display: flex; 
            flex-direction: column; 
            padding: 20px 0; 
            transition: width 0.3s cubic-bezier(0.4, 0, 0.2, 1); 
            overflow: hidden; 
            white-space: nowrap; 
            position: fixed; 
            height: 100vh; 
            z-index: 1000; 
        }
        .sidebar:hover { width: 220px; box-shadow: 10px 0 30px rgba(0,0,0,0.5); }
        .sidebar-item { width: 100%; padding: 15px 0; display: flex; align-items: center; color: var(--text-muted); text-decoration: none; transition: all 0.2s; border-left: 3px solid transparent; }
        .sidebar-icon-wrapper { min-width: 70px; display: flex; justify-content: center; align-items: center; }
        .sidebar-item span { opacity: 0; transform: translateX(-10px); transition: all 0.3s; font-size: 14px; font-weight: 500; }
        .sidebar:hover .sidebar-item span { opacity: 1; transform: translateX(0); }
        .sidebar-item.active { color: var(--neon-red); border-left: 3px solid var(--neon-red); background: rgba(239, 68, 68, 0.05); }
        .sidebar-item.blue-active { color: var(--neon-blue); border-left: 3px solid var(--neon-blue); }
        .sidebar-item:hover { color: var(--text-main); }

        /* --- CONTENT --- */
        .main-content { margin-left: 70px; padding: 40px; width: 100%; box-sizing: border-box; }
        .firewall-card { background: var(--bg-card); border: 1px solid var(--neon-red); border-radius: 12px; padding: 25px; box-shadow: 0 0 20px rgba(239, 68, 68, 0.1); }
        h1 { color: var(--neon-red); letter-spacing: 2px; text-transform: uppercase; margin-top: 0; }
        .ip-table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        .ip-table th { text-align: left; color: #9ca3af; padding: 12px; border-bottom: 1px solid #1f2937; font-size: 13px; }
        .ip-table td { padding: 12px; border-bottom: 1px solid rgba(255,255,255,0.05); font-size: 14px; }
        .input-group { display: flex; gap: 10px; margin-bottom: 20px; }
        input { background: #0b1120; border: 1px solid #1f2937; color: white; padding: 10px; border-radius: 6px; flex: 1; outline: none; }
        input:focus { border-color: var(--neon-red); }
        .btn-block { background: var(--neon-red); color: white; border: none; padding: 10px 25px; border-radius: 6px; cursor: pointer; font-weight: bold; transition: 0.3s; }
        .btn-block:hover { opacity: 0.8; box-shadow: 0 0 10px var(--neon-red); }
        .btn-unblock { background: none; border: 1px solid #22c55e; color: #22c55e; padding: 5px 10px; border-radius: 4px; cursor: pointer; transition: 0.3s; }
        .btn-unblock:hover { background: rgba(34, 197, 94, 0.1); }
    </style>
</head>
<body>
    <aside class="sidebar">
        <a href="{{ route('monitor') }}" class="sidebar-item">
            <div class="sidebar-icon-wrapper">
                <i class="fas fa-desktop"></i>
            </div>
            <span>Dashboard</span>
        </a>
        <a href="{{ route('network.index') }}" class="sidebar-item">
            <div class="sidebar-icon-wrapper">
                <i class="fas fa-network-wired"></i>
            </div>
            <span>Network Center</span>
        </a>
        <a href="{{ route('firewall.index') }}" class="sidebar-item active">
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
        <h1>🛡️ Security Firewall</h1>
        <p style="color: #9ca3af; margin-bottom: 30px;">Quản lý quy tắc truy cập và ngăn chặn tấn công hệ thống</p>

        <div class="firewall-card">
            <form action="{{ route('firewall.block') }}" method="POST">
                @csrf
                <div class="input-group">
                    <input type="text" name="ip_address" placeholder="Địa chỉ IP " required>
                    <input type="text" name="reason" placeholder="Lý do chặn ">
                    <button type="submit" class="btn-block">CHẶN IP NGAY</button>
                </div>
            </form>

            <table class="ip-table">
                <thead>
                    <tr>
                        <th>IP ADDRESS</th>
                        <th>REASON</th>
                        <th>STATUS</th>
                        <th>ACTION</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($blacklists as $item)
                    <tr>
                        <td style="color: var(--neon-red); font-weight: bold;">{{ $item->ip_address }}</td>
                        <td>{{ $item->reason }}</td>
                        <td><span style="color: #ef4444;"><i class="fas fa-circle" style="font-size: 8px;"></i> Blocked</span></td>
                        <td>
                            <form action="{{ route('firewall.unblock', $item->id) }}" method="POST" onsubmit="return confirm('Bạn có chắc muốn gỡ chặn IP này?')">
                                @csrf
                                <button type="submit" class="btn-unblock">Gỡ chặn</button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" style="text-align: center; color: var(--text-muted); padding: 40px;">
                            Chưa có địa chỉ IP nào trong danh sách đen.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </main>
</body>
</html>