<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Aegis Intelligence Center</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
        
        /* --- TÙY CHỈNH THANH CUỘN (SCROLLBAR) ĐẸP MẮT --- */
        ::-webkit-scrollbar { width: 6px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: #334155; border-radius: 10px; }
        ::-webkit-scrollbar-thumb:hover { background: var(--neon-cyan); }

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
        .ai-grid { display: grid; grid-template-columns: 1fr 1.2fr; gap: 25px; margin-top: 20px; align-items: start; }
        
        /* --- TERMINAL --- */
        .ai-terminal { 
            background: #000; border: 1px solid var(--border-color); border-radius: 12px; 
            padding: 25px; font-family: 'Courier New', monospace; 
            box-shadow: inset 0 0 20px rgba(0,0,0,1); height: 550px; position: relative;
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
        .line-ai { color: var(--neon-cyan); }
        .scan-line { width: 100%; height: 2px; background: var(--neon-cyan); opacity: 0.3; position: absolute; top: 0; left: 0; animation: scan 4s linear infinite; z-index: 2; }
        @keyframes scan { 0% { top: 0; } 100% { top: 100%; } }

        /* --- CHAT INTERFACE MỚI (CÓ LỊCH SỬ) --- */
        .chat-container { 
            background: var(--bg-card); border: 1px solid var(--border-color); 
            border-radius: 12px; display: flex; flex-direction: column; 
            height: 550px; overflow: hidden; box-shadow: 0 10px 30px rgba(0,0,0,0.5);
        }
        .chat-header { 
            padding: 15px 20px; background: rgba(0,0,0,0.5); border-bottom: 1px solid var(--border-color); 
            display: flex; align-items: center; justify-content: space-between;
        }
        .chat-title { font-size: 13px; font-weight: bold; color: var(--text-muted); text-transform: uppercase; letter-spacing: 1px; display: flex; align-items: center; gap: 8px; }
        .status-dot { width: 8px; height: 8px; background: #22c55e; border-radius: 50%; box-shadow: 0 0 8px #22c55e; animation: pulse 2s infinite; }
        @keyframes pulse { 0% { opacity: 1; } 50% { opacity: 0.4; } 100% { opacity: 1; } }

        .chat-history { flex: 1; padding: 20px; overflow-y: auto; display: flex; flex-direction: column; gap: 20px; }
        
        .msg-row { display: flex; width: 100%; }
        .msg-row.user { justify-content: flex-end; }
        .msg-row.ai { justify-content: flex-start; align-items: flex-end; gap: 12px; }
        
        .msg-bubble { max-width: 80%; padding: 12px 18px; font-size: 14px; line-height: 1.6; }
        .msg-bubble.user { 
            background: linear-gradient(135deg, var(--neon-cyan), #0284c7); color: white; 
            border-radius: 16px 16px 2px 16px; box-shadow: 0 4px 15px rgba(34, 211, 238, 0.2); 
        }
        .msg-bubble.ai { 
            background: rgba(168, 85, 247, 0.1); border: 1px solid rgba(168, 85, 247, 0.3); 
            color: #e2e8f0; border-radius: 16px 16px 16px 2px; 
        }
        
        .ai-avatar { 
            width: 36px; height: 36px; border-radius: 50%; 
            background: linear-gradient(45deg, var(--neon-purple), #7e22ce); 
            display: flex; align-items: center; justify-content: center; 
            font-size: 16px; flex-shrink: 0; box-shadow: 0 0 10px rgba(168, 85, 247, 0.5); color: white;
        }

        /* --- Ô NHẬP LỆNH & ICON WRAPPER --- */
        .chat-input-area { padding: 15px 20px; background: rgba(0,0,0,0.3); border-top: 1px solid var(--border-color); display: flex; gap: 12px; align-items: center; }
        .chat-input { 
            flex: 1; background: #020617; border: 1px solid var(--border-color); 
            color: white; padding: 14px 20px; border-radius: 30px; font-size: 14px; transition: 0.3s; 
        }
        .chat-input:focus { outline: none; border-color: var(--neon-cyan); box-shadow: 0 0 15px rgba(34, 211, 238, 0.15); }
        
        .icon-wrapper-btn { 
            width: 46px; height: 46px; border-radius: 50%; 
            background: linear-gradient(45deg, var(--neon-cyan), #0284c7); 
            display: flex; align-items: center; justify-content: center; 
            cursor: pointer; border: none; color: white; font-size: 16px;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); flex-shrink: 0;
        }
        .icon-wrapper-btn:hover { transform: scale(1.1) rotate(10deg); box-shadow: 0 0 20px var(--neon-cyan); }
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
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px;">
            <p style="color: var(--text-muted); letter-spacing: 1px; margin: 0;">Mô đun Tương tác NLP & Phân tích trực quan</p>
            <div style="color: #22c55e; font-size: 14px; font-weight: bold;"><i class="fas fa-shield-check"></i> Health Score: {{ $score }}/100</div>
        </div>

        <div class="ai-grid">
            <div class="ai-terminal" id="terminal">
                <div class="scan-line"></div>
                <div class="terminal-line line-success">[SYSTEM] Aegis Neural Core v4.0 (Gemini) active...</div>
                <div id="dynamic-logs"></div>
            </div>

            <div class="chat-container">
                <div class="chat-header">
                    <div class="chat-title"><i class="fas fa-comment-alt-code"></i> Aegis Secure Console</div>
                    <div style="display: flex; align-items: center; gap: 8px; font-size: 12px; color: var(--text-muted);">
                        <div class="status-dot"></div> AI Online
                    </div>
                </div>

                <div class="chat-history" id="chat-history"></div>

                <form action="{{ route('ai.index') }}" method="GET" class="chat-input-area">
                    <input type="text" name="ai_command" class="chat-input" placeholder="Nhập lệnh phân tích (VD: Trích xuất biểu đồ RAM)..." required autocomplete="off">
                    
                    <button type="submit" class="icon-wrapper-btn">
                        <i class="fas fa-paper-plane"></i>
                    </button>
                </form>
            </div>
        </div>
    </main>

    <script>
        // --- LOGIC TERMINAL ---
        const dynamicLogs = [
            { type: 'line-success', text: "[OK] Đã xác thực kết nối qua API Key." },
            @foreach($insights as $insight)
            { type: 'line-ai', text: "{!! $insight !!}" },
            @endforeach
            { type: 'line-info', text: "> Hệ thống đang phân tích các gói tin nền..." }
        ];
        let index = 0;
        const logContainer = document.getElementById('dynamic-logs');
        function printLog() {
            if (index < dynamicLogs.length) {
                const div = document.createElement('div');
                div.className = `terminal-line ${dynamicLogs[index].type}`;
                div.innerHTML = dynamicLogs[index].text;
                logContainer.appendChild(div);
                document.getElementById('terminal').scrollTop = 9999;
                index++;
                setTimeout(printLog, 800); 
            }
        }
        setTimeout(printLog, 500);

        // --- LOGIC LƯU TRỮ VÀ HIỂN THỊ LỊCH SỬ CHAT (SESSION STORAGE) ---
        const chatHistoryEl = document.getElementById('chat-history');
        
        // Khởi tạo lịch sử nếu chưa có
        let chatHistory = JSON.parse(sessionStorage.getItem('aegis_chat')) || [
            { sender: 'ai', text: 'Xin chào! Tôi là trí tuệ nhân tạo Aegis. Tôi đã sẵn sàng phân tích dữ liệu Server của bạn.' }
        ];

        // Nếu vừa gửi lệnh form, lấy dữ liệu từ Server đẩy vào mảng
        @if(request()->has('ai_command'))
            const userCmd = "{!! addslashes(request()->get('ai_command')) !!}";
            const aiResp = "{!! addslashes($aiResponse ?? '') !!}";
            
            // Chống nhân bản (duplicate) khi nhấn F5
            const lastMsg = chatHistory[chatHistory.length - 1];
            if (!lastMsg || lastMsg.text !== aiResp) {
                chatHistory.push({ sender: 'user', text: userCmd });
                chatHistory.push({ sender: 'ai', text: aiResp });
                
                // Giữ lịch sử không quá dài (Tối đa 20 tin nhắn)
                if(chatHistory.length > 20) chatHistory = chatHistory.slice(chatHistory.length - 20);
                sessionStorage.setItem('aegis_chat', JSON.stringify(chatHistory));
            }
        @endif

        // Hàm Render giao diện chat
        function renderChat() {
            chatHistoryEl.innerHTML = '';
            chatHistory.forEach(msg => {
                const row = document.createElement('div');
                row.className = `msg-row ${msg.sender}`;
                
                if (msg.sender === 'ai') {
                    row.innerHTML = `
                        <div class="ai-avatar"><i class="fas fa-robot"></i></div>
                        <div class="msg-bubble ai">${msg.text}</div>
                    `;
                } else {
                    row.innerHTML = `<div class="msg-bubble user">${msg.text}</div>`;
                }
                chatHistoryEl.appendChild(row);
            });

            // Nếu có dữ liệu Biểu đồ (từ Controller gửi sang) thì vẽ tiếp vào tin nhắn cuối
            @if(!empty($chartData))
                const chartRow = document.createElement('div');
                chartRow.className = 'msg-row ai';
                chartRow.innerHTML = `
                    <div class="ai-avatar" style="visibility: hidden;"></div>
                    <div class="msg-bubble ai" style="width: 100%; max-width: 85%; padding: 15px;">
                        <div style="height: 220px; width: 100%;">
                            <canvas id="aegisChart"></canvas>
                        </div>
                    </div>
                `;
                chatHistoryEl.appendChild(chartRow);
            @endif

            // Tự động cuộn xuống cuối cùng
            chatHistoryEl.scrollTop = chatHistoryEl.scrollHeight;
        }

        // Gọi hàm hiển thị
        renderChat();

        // --- LOGIC VẼ BIỂU ĐỒ VÀO KHUNG CHAT ---
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
                        backgroundColor: "rgba(34, 211, 238, 0.15)",
                        borderWidth: 2,
                        tension: 0.4, 
                        pointRadius: 3, 
                        pointBackgroundColor: "{!! $chartData['color'] !!}"
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        x: { ticks: { color: "#94a3b8", font: { size: 10 } }, grid: { color: "#1e293b", drawBorder: false } },
                        y: { ticks: { color: "#94a3b8", font: { size: 10 } }, grid: { color: "#1e293b", drawBorder: false }, beginAtZero: true, max: 100 }
                    },
                    plugins: { legend: { labels: { color: "#f3f4f6", font: { size: 13 } } } }
                }
            });
        @endif
    </script>
</body>
</html>