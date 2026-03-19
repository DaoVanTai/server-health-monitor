<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Firewall Management - Security Center</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --bg-main: #0b1120; --bg-card: #111827; --neon-red: #ef4444; --text-main: #f3f4f6;
        }
        body { background: var(--bg-main); color: var(--text-main); font-family: 'Segoe UI', sans-serif; margin: 0; display: flex; }
        .sidebar { width: 70px; background: #0f172a; height: 100vh; border-right: 1px solid #1f2937; position: fixed; }
        .main-content { margin-left: 70px; padding: 40px; width: 100%; }
        .firewall-card { background: var(--bg-card); border: 1px solid var(--neon-red); border-radius: 12px; padding: 25px; box-shadow: 0 0 20px rgba(239, 68, 68, 0.1); }
        h1 { color: var(--neon-red); letter-spacing: 2px; text-transform: uppercase; }
        .ip-table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        .ip-table th { text-align: left; color: #9ca3af; padding: 12px; border-bottom: 1px solid #1f2937; }
        .ip-table td { padding: 12px; border-bottom: 1px solid rgba(255,255,255,0.05); }
        .input-group { display: flex; gap: 10px; margin-bottom: 20px; }
        input { background: #0b1120; border: 1px solid #1f2937; color: white; padding: 10px; border-radius: 6px; flex: 1; }
        .btn-block { background: var(--neon-red); color: white; border: none; padding: 10px 25px; border-radius: 6px; cursor: pointer; font-weight: bold; }
        .btn-unblock { background: none; border: 1px solid #22c55e; color: #22c55e; padding: 5px 10px; border-radius: 4px; cursor: pointer; }
    </style>
</head>
<body>
    <div class="sidebar"></div> <main class="main-content">
        <h1>🛡️ Security Firewall</h1>
        <p style="color: #9ca3af;">Quản lý quy tắc truy cập và ngăn chặn tấn công</p>

        <div class="firewall-card">
            <form action="{{ route('firewall.block') }}" method="POST">
                @csrf
                <div class="input-group">
                    <input type="text" name="ip_address" placeholder="Địa chỉ IP (Ví dụ: 1.2.3.4)" required>
                    <input type="text" name="reason" placeholder="Lý do chặn (Ví dụ: Brute Force Attack)">
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
                    @foreach($blacklists as $item)
                    <tr>
                        <td style="color: var(--neon-red); font-weight: bold;">{{ $item->ip_address }}</td>
                        <td>{{ $item->reason }}</td>
                        <td><span style="color: #ef4444;">● Blocked</span></td>
                        <td>
                            <form action="{{ route('firewall.unblock', $item->id) }}" method="POST">
                                @csrf
                                <button type="submit" class="btn-unblock">Gỡ chặn</button>
                            </form>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </main>
</body>
</html>