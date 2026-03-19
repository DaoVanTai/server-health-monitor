<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Aegis Intelligence Center</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --bg-main: #020617; 
            --bg-card: #0f172a;
            --neon-cyan: #22d3ee;
            --neon-purple: #a855f7;
            --text-main: #f3f4f6;
            --text-muted: #64748b;
            --border-color: #1e293b;
        }
        body { background: var(--bg-main); color: var(--text-main); font-family: 'Segoe UI', sans-serif; margin: 0; display: flex; min-height: 100vh; overflow-x: hidden; }
        
        /* --- SIDEBAR --- */
        .sidebar { 
            width: 70px; background-color: #020617; border-right: 1px solid var(--border-color); 
            display: flex; flex-direction: column; padding: 20px 0; position: fixed; 
            height: 100vh; z-index: 1000; transition: width 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            overflow: hidden; white-space: nowrap;
        }
        .sidebar:hover { width: 220px; box-shadow: 10px 0 30px rgba(0,0,0,0.5); }
        .sidebar-item { 
            width: 100%; padding: 15px 0; display: flex; align-items: center; 
            color: var(--text-muted); text-decoration: none; transition: all 0.2s; 
            border-left: 3px solid transparent; 
        }
        .sidebar-icon-wrapper { min-width: 70px; display: flex; justify-content: center; align-items: center; font-size: 20px; }
        .sidebar-item span { opacity: 0; transform: translateX(-10px); transition: all 0.3s; font-size: 14px; font-weight: 500; }
        .sidebar:hover .sidebar-item span { opacity: 1; transform: translateX(0); }
        .sidebar-item.active { color: var(--neon-cyan); border-left: 3px solid var(--neon-cyan); background: rgba(34, 211, 238, 0.05); }
        .sidebar-item:hover { color: var(--text-main); }

        /* --- CONTENT --- */
        .main-content { margin-left: 70px; padding: 40px; width: 100%; box-sizing: border-box; }
        .ai-header { color: var(--neon-cyan); font-weight: bold; margin-bottom: 10px; display: flex; align-items: center; gap: 12px; text-shadow: 0 0 10px rgba(34, 211, 238, 0.3); }
        
        .ai-grid { display: grid; grid-template-columns: 2fr 1fr; gap: 25px; margin-top: 20px; }
        
        .ai-terminal { 
            background: #000; border: 1px solid var(--border-color); border-radius: 12px; 
            padding: 25px; font-family: 'Courier New', monospace; 
            box-shadow: inset 0 0 20px rgba(0,0,0,1); min-height: 450px; position: relative;
            overflow-y: auto;
        }
        .ai-terminal::before {
            content: ""; position: absolute; top: 0; left: 0; width: 100%; height: 100%;
            background: linear-gradient(rgba(18, 16, 16, 0) 50%, rgba(0, 0, 0, 0.25) 50%), linear-gradient(90deg, rgba(255, 0, 0, 0.06), rgba(0, 255, 0, 0.02), rgba(0, 0, 255, 0.06));
            background-size: 100% 2px, 3px 100%; pointer-events: none;
        }
        
        .terminal-line { margin-bottom: 12px; line-height: 1.5; font-size: 14px; position: relative; z-index: 1; }
        .line-success { color: #22c55e; }
        .line-info { color: var(--text-muted); }
        .line-warning { color: #f59e0b; }
        .line-danger { color: #ef4444; }
        .line-ai { color: var(--neon-cyan); }

        .stat-card { background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 12px; padding: 20px; margin-bottom: 20px; transition: 0.3s; }
        .stat-value { font-size: 32px; font-weight: bold; color: var(--neon-purple); margin: 5px 0; }
        
        .btn-ai { 
            width: 100%; background: linear-gradient(45deg, var(--neon-purple), #7e22ce); 
            color: white; border: none; padding: 12px; border-radius: 8px; 
            cursor: pointer; font-weight: bold; margin-top: 15px; transition: 0.3s;
        }
        .btn-ai:hover { box-shadow: 0 0 15px var(--neon-purple); filter: brightness(1.1); }
        
        .scan-line {
            width: 100%; height: 2px; background: var(--neon-cyan); opacity: 0.3;
            position: absolute; top: 0; left: 0; animation: scan 4s linear infinite; z-index: 2;
        }
        @keyframes scan { 0% { top: 0; } 100% { top: 100%; } }
    </style>
</head>
<body>
    <aside class="sidebar">
        <a href="{{ route('monitor') }}" class="sidebar-item">
            <div class="sidebar-icon-wrapper"><i class="fas fa-desktop"></i></div>
            <span>Dashboard</span>
        </a>
        <a href="{{ route('network.index') }}" class="sidebar-item">
            <div class="sidebar-icon-wrapper"><i class="fas fa-network-wired"></i></div>
            <span>Network Center</span>
        </a>
        <a href="{{ route('firewall.index') }}" class="sidebar-item">
            <div class="sidebar-icon-wrapper"><i class="fas fa-shield-alt"></i></div>
            <span>Security Firewall</span>
        </a>
        <a href="{{ route('ai.index') }}" class="sidebar-item active">
            <div class="sidebar-icon-wrapper"><i class="fas fa-brain"></i></div>
            <span>Aegis Intelligence</span>
        </a>
    </aside>

    <main class="main-content">
        <div class="ai-header" style="font-size: 28px;">
            <i class="fas fa-atom fa-spin" style="color: var(--neon-purple);"></i> AEGIS INTELLIGENCE CENTER
        </div>
        <p style="color: var(--text-muted); margin-bottom: 30px; letter-spacing: 1px;">AI-Driven Threat Detection & System Analysis Engine</p>

        <div class="ai-grid">
            <div class="ai-terminal" id="terminal">
                <div class="scan-line"></div>
                <div class="terminal-line line-success">[SYSTEM] Aegis Neural Core v3.0.5 initialized...</div>
                <div class="terminal-line line-info">[INFO] Connecting to local database 'server-health'...</div>
                <div class="terminal-line line-info">[INFO] Analyzing metric patterns from database...</div>
                <div id="dynamic-logs"></div>
            </div>

            <div>
                <div class="stat-card">
                    <div style="font-size: 11px; color: var(--text-muted); text-transform: uppercase;">Security Health Score</div>
                    <div class="stat-value" style="color: #22c55e;">{{ $score }}<span style="font-size: 14px;">/100</span></div>
                    <div style="height: 6px; background: #1e293b; border-radius: 3px; margin-top: 10px; overflow: hidden;">
                        <div style="width: {{ $score }}%; height: 100%; background: linear-gradient(90deg, #22c55e, var(--neon-cyan));"></div>
                    </div>
                </div>

                <div class="stat-card">
                    <div style="font-size: 11px; color: var(--text-muted); text-transform: uppercase;">Abnormal Activities</div>
                    <div class="stat-value" style="color: #ef4444;">{{ sprintf('%02d', $threats) }}</div>
                    <p style="font-size: 12px; color: var(--text-muted); margin: 0;">Total threats in blacklist</p>
                </div>

                <div class="stat-card" style="border-left: 4px solid var(--neon-purple);">
                    <div style="font-size: 11px; color: var(--text-muted); text-transform: uppercase;">AI Recommendation</div>
                    <p style="font-size: 14px; line-height: 1.6; margin-top: 15px; color: #e2e8f0;">
                        @if($score < 80)
                            "Cảnh báo: Chỉ số an toàn đang giảm. Hãy kiểm tra các IP lạ và giải phóng tài nguyên CPU ngay."
                        @else
                            "Hệ thống hiện tại đang ở trạng thái tối ưu. Tiếp tục duy trì các quy tắc tường lửa hiện có."
                        @endif
                    </p>
                    <button class="btn-ai" onclick="alert('Aegis AI: Đang tối ưu hóa các tiến trình hệ thống...')">
                        <i class="fas fa-bolt"></i> APPLY AI OPTIMIZATION
                    </button>
                </div>
            </div>
        </div>
    </main>

    <script>
        const dynamicLogs = [
            { type: 'line-success', text: "[OK] Kết nối cơ sở dữ liệu thành công." },
            @foreach($insights as $insight)
            { type: 'line-ai', text: "{!! $insight !!}" },
            @endforeach
            { type: 'line-info', text: "> Aegis AI tiếp tục giám sát các luồng dữ liệu thời gian thực..." }
        ];

        let index = 0;
        const logContainer = document.getElementById('dynamic-logs');

        function printLog() {
            if (index < dynamicLogs.length) {
                const div = document.createElement('div');
                div.className = `terminal-line ${dynamicLogs[index].type}`;
                div.innerHTML = dynamicLogs[index].text;
                logContainer.appendChild(div);
                
                const terminal = document.getElementById('terminal');
                terminal.scrollTop = terminal.scrollHeight;
                
                index++;
                setTimeout(printLog, 1500);
            }
        }

        setTimeout(printLog, 1000);
    </script>
</body>
</html>