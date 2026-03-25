<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>SSH Login Tracker - Security Center</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --bg-main: #0b1120; --bg-card: #111827; 
            --neon-red: #ef4444; --neon-green: #10b981; 
            --neon-yellow: #eab308; --neon-blue: #3b82f6; 
            --text-main: #f3f4f6; --text-muted: #9ca3af; --border-color: #1f2937;
        }
        body { background: var(--bg-main); color: var(--text-main); font-family: 'Segoe UI', sans-serif; margin: 0; display: flex; min-height: 100vh; }
        
        .sidebar { width: 70px; background-color: #0f172a; border-right: 1px solid var(--border-color); display: flex; flex-direction: column; padding: 20px 0; transition: width 0.3s; overflow: hidden; white-space: nowrap; position: fixed; height: 100vh; z-index: 1000; }
        .sidebar:hover { width: 220px; box-shadow: 10px 0 30px rgba(0,0,0,0.5); }
        .sidebar-item { width: 100%; padding: 15px 0; display: flex; align-items: center; color: var(--text-muted); text-decoration: none; transition: all 0.2s; border-left: 3px solid transparent; }
        .sidebar-icon-wrapper { min-width: 70px; display: flex; justify-content: center; align-items: center; }
        .sidebar-item span { opacity: 0; transform: translateX(-10px); transition: all 0.3s; font-size: 14px; font-weight: 500; }
        .sidebar:hover .sidebar-item span { opacity: 1; transform: translateX(0); }
        .sidebar-item.active { color: var(--neon-blue); border-left: 3px solid var(--neon-blue); background: rgba(59, 130, 246, 0.05); }

        .main-content { margin-left: 70px; padding: 40px; width: calc(100% - 70px); box-sizing: border-box; }
        h1 { color: var(--neon-blue); letter-spacing: 2px; text-transform: uppercase; margin-top: 0; display: flex; justify-content: space-between; align-items: center;}
        
        .card { background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 12px; padding: 25px; margin-bottom: 25px; }
        .grid-3col { display: grid; grid-template-columns: 1fr 1.5fr; gap: 20px; }
        @media (max-width: 1200px) { .grid-3col { grid-template-columns: 1fr; } }

        .log-table { width: 100%; border-collapse: collapse; }
        .log-table th { text-align: left; color: #9ca3af; padding: 15px; border-bottom: 1px solid #374151; font-size: 13px; text-transform: uppercase;}
        .log-table td { padding: 15px; border-bottom: 1px solid rgba(255,255,255,0.03); font-size: 14px; }
        .log-table tbody tr:hover { background-color: rgba(59, 130, 246, 0.08); }

        .custom-scrollbar::-webkit-scrollbar { width: 6px; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: #374151; border-radius: 10px; }

        .alert-box { background: rgba(239, 68, 68, 0.1); border-left: 4px solid var(--neon-red); padding: 20px; border-radius: 8px; margin-bottom: 25px; display: flex; align-items: center; justify-content: space-between;}
        .btn-ban { background: var(--neon-red); color: white; border: none; padding: 6px 12px; border-radius: 4px; cursor: pointer; font-size: 12px; text-transform: uppercase; font-weight: bold;}
        .btn-ban:hover { box-shadow: 0 0 10px rgba(239, 68, 68, 0.5); }
    </style>
</head>
<body>
    <aside class="sidebar">
        <a href="{{ route('monitor') }}" class="sidebar-item"><div class="sidebar-icon-wrapper"><i class="fas fa-desktop"></i></div><span>Dashboard</span></a>
        <a href="{{ route('network.index') }}" class="sidebar-item"><div class="sidebar-icon-wrapper"><i class="fas fa-network-wired"></i></div><span>Network Center</span></a>
        <a href="{{ route('firewall.index') }}" class="sidebar-item"><div class="sidebar-icon-wrapper"><i class="fas fa-shield-alt"></i></div><span>Firewall</span></a>
        <a href="{{ route('ssh.tracker') }}" class="sidebar-item active"><div class="sidebar-icon-wrapper"><i class="fas fa-user-secret"></i></div><span>SSH Tracker</span></a>
        <a href="{{ route('logs.index') }}" class="sidebar-item"><div class="sidebar-icon-wrapper"><i class="fas fa-clipboard-list"></i></div><span>Log & Analytics</span></a>
    </aside>

    <main class="main-content">
        <h1><span><i class="fas fa-terminal"></i> MÁY QUÉT NHẬT KÝ ĐĂNG NHẬP (SSH TRACKER)</span></h1>
        <p style="color: var(--text-muted); margin-top: -10px; margin-bottom: 30px;">Hệ thống tự động đọc và phân tích file <code>/var/log/auth.log</code> để phát hiện rà quét mật khẩu.</p>

        @if(count($recentLogins) > 0)
        <div class="card" style="border: 1px solid var(--neon-green); box-shadow: 0 0 15px rgba(16, 185, 129, 0.1);">
            <h3 style="margin-top:0; color:var(--neon-green);"><i class="fas fa-door-open"></i> LỊCH SỬ ĐĂNG NHẬP THÀNH CÔNG (CẢNH BÁO CAO)</h3>
            <p style="color: #9ca3af; font-size: 14px;">⚠️ Nếu có địa chỉ IP lạ xuất hiện ở đây, máy chủ của bạn ĐÃ BỊ HACKER CHIẾM QUYỀN.</p>
            <div class="custom-scrollbar" style="max-height: 250px; overflow-y: auto;">
                <table class="log-table">
                    <thead style="position: sticky; top: 0; background: var(--bg-card);">
                        <tr><th>Thời gian</th><th>Tài khoản</th><th>IP Truy cập</th><th>Trạng thái</th></tr>
                    </thead>
                    <tbody>
                        @foreach($recentLogins as $login)
                        <tr>
                            <td style="color: var(--text-muted);">{{ $login['time'] }}</td>
                            <td style="font-weight: bold; color: white;">{{ $login['user'] }}</td>
                            <td style="color: var(--neon-blue); font-family: monospace;">{{ $login['ip'] }}</td>
                            <td><span style="color: var(--neon-green); font-weight: bold;">[OK] Accepted</span></td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @endif

        <div class="grid-3col">
            <div class="card">
                <h3 style="margin-top:0; color:var(--neon-yellow);"><i class="fas fa-users"></i> TOP TÀI KHOẢN BỊ DÒ MẬT KHẨU</h3>
                <div class="custom-scrollbar" style="max-height: 300px; overflow-y: auto;">
                    <table class="log-table">
                        <thead style="position: sticky; top: 0; background: var(--bg-card);">
                            <tr><th>Tên tài khoản (User)</th><th>Số lần thử</th></tr>
                        </thead>
                        <tbody>
                            @forelse($topUsers as $user => $count)
                            <tr>
                                <td style="color: #cbd5e1; font-weight: bold;">{{ $user }}</td>
                                <td style="color: var(--neon-yellow);">{{ $count }} lần</td>
                            </tr>
                            @empty
                            <tr><td colspan="2" style="text-align: center; color: var(--text-muted);">Hệ thống an toàn.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="card">
                <h3 style="margin-top:0; color:var(--neon-red);"><i class="fas fa-crosshairs"></i> TOP IP ĐANG RÀ QUÉT (BRUTE-FORCE)</h3>
                <div class="custom-scrollbar" style="max-height: 300px; overflow-y: auto;">
                    <table class="log-table">
                        <thead style="position: sticky; top: 0; background: var(--bg-card);">
                            <tr><th>Địa chỉ IP</th><th>Số lần sai</th><th>Tài khoản nhắm tới</th><th>Hành động</th></tr>
                        </thead>
                        <tbody>
                            @forelse($topAttackers as $ip => $data)
                            <tr>
                                <td style="color: var(--neon-red); font-family: monospace; font-weight: bold;">{{ $ip }}</td>
                                <td style="color: white;">{{ $data['count'] }}</td>
                                <td style="color: var(--text-muted); font-size: 12px;">{{ implode(', ', array_slice($data['users'], 0, 3)) }}</td>
                                <td>
                                    <form action="{{ route('firewall.block') }}" method="POST">
                                        @csrf
                                        <input type="hidden" name="ip_address" value="{{ $ip }}">
                                        <input type="hidden" name="reason" value="SSH Brute-force Detected">
                                        <button type="submit" class="btn-ban"><i class="fas fa-ban"></i> Block IP</button>
                                    </form>
                                </td>
                            </tr>
                            @empty
                            <tr><td colspan="4" style="text-align: center; color: var(--text-muted);">Không phát hiện rà quét.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>
</body>
</html>