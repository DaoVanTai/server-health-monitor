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
        
        /* --- TERMINAL --- */
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
        
        /* --- CHAT UI CSS MỚI --- */
        .chat-input-wrapper { display: flex; gap: 12px; margin-top: 15px; }
        .chat-input { 
            flex: 1; background: rgba(0,0,0,0.6); border: 1px solid var(--border-color); 
            color: var(--text-main); padding: 15px 20px; border-radius: 12px; 
            font-size: 14px; transition: all 0.3s ease; 
        }
        .chat-input:focus { 
            outline: none; border-color: var(--neon-cyan); 
            box-shadow: 0 0 15px rgba(34, 211, 238, 0.2); background: rgba(0,0,0,0.8); 
        }
        .btn-send { 
            background: linear-gradient(45deg, var(--neon-cyan), #0284c7); 
            color: white; border: none; padding: 0 25px; border-radius: 12px; 
            cursor: pointer; font-weight: bold; transition: 0.3s; font-size: 14px;
            display: flex; align-items: center; gap: 8px;
        }
        .btn-send:hover { box-shadow: 0 0 15px var(--neon-cyan); transform: translateY(-2px); }
        
        .ai-response-box { 
            background: rgba(168, 85, 247, 0.1); border-left: 4px solid var(--neon-purple); 
            padding: 15px 20px; border-radius: 0 12px 12px 0; margin-top: 20px; margin-bottom: 20px; 
            font-size: 14px; line-height: 1.6; color: #e2e8f0; 
            display: flex; gap: 15px; align-items: flex-start;
        }
        .ai-avatar { 
            width: 35px; height: 35px; border-radius: 50%; 
            background: linear-gradient(45deg, var(--neon-purple), var(--neon-cyan)); 
            display: flex; align-items: center; justify-content: center; 
            font-size: 16px; flex-shrink: 0; box-shadow: 0 0 10px var(--neon-purple); 
        }

        .scan-line {
            width: 100%; height: 2px; background: var(--neon-cyan); opacity: 0.3;
            position: absolute; top: 0; left: 0; animation: scan 4s linear infinite; z-index: 2;
        }
        @keyframes scan { 0% { top: 0; } 100% { top: 100%; } }
    </style>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
        <p style="color: var(--text-muted); margin-bottom: 30px; letter-spacing: 1px;">Hệ thống tương tác và trực quan hóa dữ liệu AI</p>

        <div class="ai-grid">
            <div class="ai-terminal" id="terminal">
                <div class="scan-line"></div>
                <div class="terminal-line line-success">[SYSTEM] Aegis Neural Core v4.0 initialized...</div>
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

                <div class="stat-card" style="border-left: 4px solid var(--neon-cyan); display: flex; flex-direction: column;">
                    <div style="font-size: 11px; color: var(--text-muted); text-transform: uppercase; font-weight: bold; letter-spacing: 1px;">
                        <i class="fas fa-terminal"></i> Aegis Command Interface
                    </div>
                    
                    <form action="{{ route('ai.index') }}" method="GET" class="chat-input-wrapper">
                        <input type="text" name="ai_command" class="chat-input" placeholder="Nhắn tin cho Aegis AI... (VD: vẽ biểu đồ CPU)" required autocomplete="off">
                        <button type="submit" class="btn-send">
                            <i class="fas fa-paper-plane"></i> Gửi lệnh
                        </button>
                    </form>

                    <div class="ai-response-box">
                        <div class="ai-avatar"><i class="fas fa-robot" style="color: white;"></i></div>
                        <div style="flex: 1; padding-top: 5px;">
                            {{ $aiResponse ?? 'Xin chào! Tôi là Aegis AI. Hệ thống đang hoạt động ổn định. Tôi có thể giúp gì cho bạn?' }}
                        </div>
                    </div>
                    
                    @if(!empty($chartData))
                        <div style="height: 250px; background: rgba(0,0,0,0.3); border-radius: 12px; padding: 15px; border: 1px solid var(--border-color);">
                            <canvas id="aegisChart"></canvas>
                        </div>
                    @else
                        <div style="height: 100px; display: flex; align-items: center; justify-content: center; border: 1px dashed var(--border-color); border-radius: 12px; background: rgba(0,0,0,0.2);">
                            <p style="color: var(--text-muted); font-size: 13px; font-style: italic;"><i class="fas fa-chart-line"></i> Chưa có biểu đồ được yêu cầu.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </main>

    <script>
        // --- 1. HIỆU ỨNG GÕ CHỮ CHO TERMINAL ---
        const dynamicLogs = [
            { type: 'line-success', text: "[OK] Đã kết nối cơ sở dữ liệu." },
            @foreach($insights as $insight)
            { type: 'line-ai', text: "{!! $insight !!}" },
            @endforeach
            { type: 'line-info', text: "> Aegis AI đang chờ lệnh tiếp theo..." }
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
                setTimeout(printLog, 800); 
            }
        }
        setTimeout(printLog, 500);

        // --- 2. LOGIC VẼ BIỂU ĐỒ (NẾU CÓ DỮ LIỆU) ---
        @if(!empty($chartData))
            const ctx = document.getElementById('aegisChart').getContext('2d');
            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: {!! json_encode($chartData['labels']) !!},
                    datasets: [{
                        label: "{!! $chartData['label'] !!}",
                        data: {!! json_encode($chartData['values']) !!},
                        borderColor: "{!! $chartData['color'] !!}",
                        backgroundColor: "rgba(34, 211, 238, 0.15)", // Nền đậm hơn chút cho đẹp
                        borderWidth: 2,
                        tension: 0.4, 
                        pointRadius: 3, // Điểm tròn to hơn
                        pointBackgroundColor: "{!! $chartData['color'] !!}"
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        x: { 
                            ticks: { color: "#94a3b8", font: { size: 11 } },
                            grid: { color: "#1e293b", drawBorder: false }
                        },
                        y: { 
                            ticks: { color: "#94a3b8", font: { size: 11 } },
                            grid: { color: "#1e293b", drawBorder: false },
                            beginAtZero: true,
                            max: 100
                        }
                    },
                    plugins: {
                        legend: { labels: { color: "#f3f4f6", font: { size: 13, family: "'Segoe UI', sans-serif" } } }
                    }
                }
            });
        @endif
    </script>
</body>
</html>