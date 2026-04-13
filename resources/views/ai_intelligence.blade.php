<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Aegis Intelligence Center - Security System</title>
    
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Roboto+Mono:wght@400;500&display=swap" rel="stylesheet">
    
    <style>
        :root {
            /* Vercel/Linear Deep Dark Theme Palette */
            --bg-main: #000000; --bg-card: #0a0a0a; --border-color: #27272a; --border-hover: #3f3f46;
            --text-main: #fafafa; --text-muted: #a1a1aa;
            --neon-purple: #c084fc; --neon-blue: #38bdf8; --neon-green: #34d399;
            --neon-red: #f87171; --neon-orange: #fbbf24; --neon-cyan: #22d3ee;
            --shadow-soft: 0 10px 30px rgba(0, 0, 0, 0.5);
        }

        * { box-sizing: border-box; }
        body { margin: 0; padding: 0; background-color: var(--bg-main); color: var(--text-main); font-family: 'Inter', -apple-system, sans-serif; display: flex; min-height: 100vh; -webkit-font-smoothing: antialiased; overflow-x: hidden;}
        
        /* --- SIDEBAR ĐỒNG BỘ --- */
        .sidebar { width: 72px; background-color: var(--bg-main); border-right: 1px solid var(--border-color); display: flex; flex-direction: column; padding: 20px 0; transition: width 0.2s ease; overflow: hidden; white-space: nowrap; position: fixed; height: 100vh; z-index: 1000;}
        .sidebar:hover { width: 240px; background-color: var(--bg-card); }
        
        .sidebar-logo-container { width: 100%; display: flex; align-items: center; padding: 0 16px; margin-bottom: 24px; padding-bottom: 24px; border-bottom: 1px solid var(--border-color); cursor: pointer;}
        .sidebar-logo { min-width: 40px; height: 40px; border-radius: 10px; background: linear-gradient(135deg, var(--neon-cyan), var(--neon-purple)); display: flex; justify-content: center; align-items: center; font-size: 20px; color: white; flex-shrink: 0; }
        .sidebar-logo-text { margin-left: 12px; opacity: 0; transition: opacity 0.2s; font-weight: 800; font-size: 16px; letter-spacing: 0.5px; background: linear-gradient(135deg, #fff, #a1a1aa); -webkit-background-clip: text; -webkit-text-fill-color: transparent;}
        .sidebar:hover .sidebar-logo-text { opacity: 1; }

        .sidebar-item { width: 100%; padding: 16px 0; display: flex; align-items: center; color: var(--text-muted); text-decoration: none; transition: all 0.2s; border-right: 2px solid transparent; }
        .sidebar-icon-wrapper { min-width: 72px; display: flex; justify-content: center; align-items: center; font-size: 1.1rem;}
        .sidebar-item span { opacity: 0; transition: opacity 0.2s; font-size: 14px; font-weight: 500;}
        .sidebar:hover .sidebar-item span { opacity: 1; }
        .sidebar-item.active { color: var(--text-main); background: rgba(255,255,255,0.03); }
        .sidebar-item:hover:not(.active) { color: var(--text-main); background: rgba(255,255,255,0.05); }

        /* --- MAIN CONTENT & TASK BAR CHUẨN --- */
        .main-content { flex: 1; margin-left: 72px; padding: 40px 48px; display: flex; flex-direction: column; gap: 24px; max-width: 1600px; margin-right: auto;}
        
        .header { display: flex; justify-content: space-between; align-items: center; padding-bottom: 8px;}
        .title-area h1 { font-size: 24px; font-weight: 700; margin: 0; color: var(--text-main); letter-spacing: -0.5px; display: flex; align-items: center; gap: 12px;}
        .title-area p { color: var(--text-muted); margin: 4px 0 0 0; font-size: 14px; font-weight: 400;}
        
        .btn-logout { background: transparent; border: 1px solid var(--border-color); color: var(--text-main); border-radius: 6px; cursor: pointer; transition: all 0.2s; display: flex; align-items: center; justify-content: center;}
        .btn-logout:hover { border-color: var(--border-hover); background: rgba(255,255,255,0.05); }

        .badge-health { background: rgba(52, 211, 153, 0.1); border: 1px solid rgba(52, 211, 153, 0.2); color: var(--neon-green); padding: 6px 14px; border-radius: 9999px; font-weight: 500; font-size: 12px; display: flex; align-items: center; gap: 8px;}
        .status-pulse { width: 8px; height: 8px; background: currentColor; border-radius: 50%; box-shadow: 0 0 8px currentColor; animation: pulse 2s infinite; }
        @keyframes pulse { 0% { opacity: 1; } 50% { opacity: 0.4; } 100% { opacity: 1; } }

        /* --- AI GRID LAYOUT --- */
        .ai-grid { display: grid; grid-template-columns: 1fr 1.5fr; gap: 24px; margin-top: 10px; align-items: stretch; height: 600px;}
        @media (max-width: 1200px) { .ai-grid { grid-template-columns: 1fr; height: auto;} }

        /* --- TERMINAL --- */
        .ai-terminal { background: #000000; border: 1px solid var(--border-color); border-radius: 12px; padding: 24px; font-family: 'Roboto Mono', monospace; height: 100%; position: relative; overflow-y: auto; box-shadow: inset 0 0 20px rgba(0,0,0,0.8);}
        .ai-terminal::before { content: ""; position: absolute; top: 0; left: 0; width: 100%; height: 100%; background: linear-gradient(rgba(18, 16, 16, 0) 50%, rgba(0, 0, 0, 0.2) 50%); background-size: 100% 4px; pointer-events: none; z-index: 0;}
        .terminal-line { margin-bottom: 12px; line-height: 1.6; font-size: 13px; position: relative; z-index: 1; word-wrap: break-word;}
        .line-success { color: var(--neon-green); }
        .line-info { color: var(--text-muted); }
        .line-ai { color: var(--neon-cyan); }
        .scan-line { width: 100%; height: 2px; background: var(--neon-cyan); opacity: 0.15; position: absolute; top: 0; left: 0; animation: scan 3s linear infinite; z-index: 2; pointer-events: none;}
        @keyframes scan { 0% { top: 0; } 100% { top: 100%; } }

        /* --- CHAT WRAPPER --- */
        .chat-wrapper { display: flex; flex-direction: column; height: 100%; background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 12px; overflow: hidden; box-shadow: var(--shadow-soft);}
        .chat-header { padding: 16px 24px; border-bottom: 1px solid var(--border-color); display: flex; align-items: center; justify-content: space-between; background: rgba(255,255,255,0.02);}
        .chat-title { font-size: 14px; font-weight: 600; color: var(--text-main); display: flex; align-items: center; gap: 10px; }
        .status-dot { width: 8px; height: 8px; background: var(--neon-green); border-radius: 50%; box-shadow: 0 0 8px var(--neon-green); animation: pulse 2s infinite; }
        
        .chat-history { flex: 1; padding: 24px; overflow-y: auto; display: flex; flex-direction: column; gap: 24px; }
        .msg-row { display: flex; width: 100%; }
        .msg-row.user { justify-content: flex-end; }
        .msg-row.ai { justify-content: flex-start; align-items: flex-start; gap: 16px; }
        .msg-bubble { max-width: 85%; padding: 14px 18px; font-size: 14px; line-height: 1.6; white-space: pre-wrap; font-weight: 400;}
        .msg-bubble.user { background: rgba(255,255,255,0.1); color: var(--text-main); border-radius: 16px 16px 4px 16px; border: 1px solid var(--border-hover);}
        .msg-bubble.ai { background: transparent; color: var(--text-main); border-radius: 16px 16px 16px 4px; padding: 0;}
        
        .ai-avatar { width: 38px; height: 38px; border-radius: 50%; background: #18181b; border: 1px solid var(--border-color); display: flex; align-items: center; justify-content: center; flex-shrink: 0; overflow: hidden; box-shadow: 0 0 10px rgba(34, 211, 238, 0.2);}
        .ai-avatar img { width: 100%; height: 100%; object-fit: cover;}

        .chat-input-area { padding: 16px 24px; border-top: 1px solid var(--border-color); display: flex; gap: 16px; align-items: center; background: rgba(255,255,255,0.02);}
        .chat-input { flex: 1; background: #000000; border: 1px solid var(--border-color); color: var(--text-main); padding: 14px 20px; border-radius: 9999px; font-size: 14px; transition: 0.2s; outline: none;}
        .chat-input::placeholder { color: #52525b; }
        .chat-input:focus { border-color: var(--neon-cyan); box-shadow: 0 0 0 1px rgba(34, 211, 238, 0.2); }
        .icon-wrapper-btn { width: 44px; height: 44px; border-radius: 50%; background: rgba(34, 211, 238, 0.1); color: var(--neon-cyan); border: 1px solid rgba(34, 211, 238, 0.2); display: flex; align-items: center; justify-content: center; cursor: pointer; font-size: 16px; transition: all 0.2s; flex-shrink: 0; outline: none;}
        .icon-wrapper-btn:hover { background: rgba(34, 211, 238, 0.2); transform: scale(1.05); }

        /* Scrollbar */
        ::-webkit-scrollbar { width: 4px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: #3f3f46; border-radius: 10px; }
        ::-webkit-scrollbar-thumb:hover { background: #52525b; }

        .dashboard-footer { text-align: right; font-size: 12px; color: var(--text-muted); margin-top: auto; padding-top: 20px;}
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
        <a href="{{ route('network.index') }}" class="sidebar-item">
            <div class="sidebar-icon-wrapper"><i class="fas fa-globe"></i></div><span>Network</span>
        </a>
        <a href="{{ route('firewall.index') }}" class="sidebar-item">
            <div class="sidebar-icon-wrapper"><i class="fas fa-shield-alt"></i></div><span>Security</span>
        </a>
        <a href="{{ route('ai.index') }}" class="sidebar-item active">
            <div class="sidebar-icon-wrapper"><i class="fas fa-sparkles"></i></div><span>AI Insight</span>
        </a>
        <a href="{{ route('logs.index') }}" class="sidebar-item">
            <div class="sidebar-icon-wrapper"><i class="fas fa-list-ul"></i></div><span>Security Audit</span>
        </a>
    </aside>

    <main class="main-content">
        <header class="header">
            <div class="title-area">
                <h1><i class="fas fa-atom fa-spin" style="color: var(--neon-cyan);"></i> Aegis Intelligence</h1>
                <p>NLP Interaction & Autonomous Security Analysis</p>
            </div>
            
            <div style="display: flex; gap: 12px; align-items: center;">
                <div class="badge-health">
                    <div class="status-pulse"></div> <span>Health Score: {{ $score ?? 100 }}/100</span>
                </div>

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

        <div class="ai-grid">
            <div class="ai-terminal" id="terminal">
                <div class="scan-line"></div>
                <div class="terminal-line line-success">[SYSTEM] Aegis Neural Core v4.0 (Realtime) active...</div>
                <div id="dynamic-logs"></div>
            </div>

            <div class="chat-wrapper">
                <div class="chat-header">
                    <div class="chat-title"><i class="fas fa-terminal" style="color: var(--text-muted)"></i> Aegis Secure Console</div>
                    <div style="display: flex; align-items: center; gap: 8px; font-size: 12px; color: var(--text-muted); font-weight: 500;">
                        <div class="status-dot"></div> Core Online
                    </div>
                </div>

                <div class="chat-history" id="chat-history"></div>

                <form action="{{ route('ai.index') }}" method="GET" class="chat-input-area">
                    <input type="text" name="ai_command" class="chat-input" placeholder="Nhập lệnh phân tích (VD: tình trạng hệ thống, ram, cpu...)" required autocomplete="off">
                    <button type="submit" class="icon-wrapper-btn">
                        <i class="fas fa-paper-plane"></i>
                    </button>
                </form>
            </div>
        </div>
        
        <div class="dashboard-footer">Aegis AI Core Engine - Powered by NLP</div>
    </main>

    <script>
        // --- 1. LOGIC TERMINAL HIỆU ỨNG GÕ CHỮ ---
        const dynamicLogs = [
            { type: 'line-success', text: "[OK] Đã thiết lập luồng trực tiếp tới Database." },
            @if(isset($insights))
                @foreach($insights as $insight)
                { type: 'line-ai', text: {!! json_encode($insight) !!} },
                @endforeach
            @endif
            { type: 'line-info', text: "> Hệ thống sẵn sàng phản hồi thông số Realtime..." }
        ];
        
        let index = 0;
        const logContainer = document.getElementById('dynamic-logs');
        const terminalEl = document.getElementById('terminal');

        function printLog() {
            if (index < dynamicLogs.length) {
                const div = document.createElement('div');
                div.className = `terminal-line ${dynamicLogs[index].type}`;
                div.innerHTML = dynamicLogs[index].text;
                logContainer.appendChild(div);
                terminalEl.scrollTop = terminalEl.scrollHeight;
                index++;
                setTimeout(printLog, 600 + Math.random() * 400); 
            }
        }
        setTimeout(printLog, 500);

        // --- 2. LOGIC QUẢN LÝ CHAT ---
        let chatHistory = JSON.parse(sessionStorage.getItem('aegis_chat_history')) || [
            { sender: 'ai', text: 'Xin chào Quản trị viên. Tôi là Aegis, AI giám sát độc quyền của hệ thống Server Health Monitoring. Bạn cần phân tích thông số gì hôm nay?' }
        ];

        @if(request()->has('ai_command'))
            const userCmd = {!! json_encode(request()->get('ai_command')) !!};
            const aiResp = {!! json_encode($aiResponse ?? '') !!};
            
            const lastMsg = chatHistory[chatHistory.length - 1];
            if (!lastMsg || lastMsg.text !== aiResp) {
                chatHistory.push({ sender: 'user', text: userCmd });
                chatHistory.push({ sender: 'ai', text: aiResp });
                
                if(chatHistory.length > 50) chatHistory = chatHistory.slice(chatHistory.length - 50);
                sessionStorage.setItem('aegis_chat_history', JSON.stringify(chatHistory));
            }
        @endif

        const chatHistoryEl = document.getElementById('chat-history');

        function renderChat() {
            chatHistoryEl.innerHTML = '';
            
            chatHistory.forEach(msg => {
                const row = document.createElement('div');
                row.className = `msg-row ${msg.sender}`;
                
                if (msg.sender === 'ai') {
                    row.innerHTML = `
                        <div class="ai-avatar"><img src="https://api.dicebear.com/7.x/bottts/svg?seed=AegisBot&backgroundColor=18181b" alt="Aegis AI"></div>
                        <div class="msg-bubble ai">${msg.text}</div>
                    `;
                } else {
                    row.innerHTML = `<div class="msg-bubble user">${msg.text}</div>`;
                }
                chatHistoryEl.appendChild(row);
            });
            chatHistoryEl.scrollTop = chatHistoryEl.scrollHeight;
        }

        renderChat();

        // --- 3. HIỆU ỨNG CHỐNG LAG KHI GỬI LỆNH ---
        document.querySelector('.chat-input-area').addEventListener('submit', function(e) {
            const btn = this.querySelector('.icon-wrapper-btn');
            const input = this.querySelector('.chat-input');
            const userText = input.value.trim();

            if(userText !== "") {
                btn.innerHTML = '<i class="fas fa-circle-notch fa-spin"></i>';
                btn.style.opacity = '0.5';
                btn.style.pointerEvents = 'none';
                input.readOnly = true;

                chatHistoryEl.innerHTML += `
                    <div class="msg-row user">
                        <div class="msg-bubble user">${userText}</div>
                    </div>`;

                chatHistoryEl.innerHTML += `
                    <div class="msg-row ai" id="ai-thinking-indicator">
                        <div class="ai-avatar"><img src="https://api.dicebear.com/7.x/bottts/svg?seed=AegisBot&backgroundColor=18181b" alt="Aegis AI"></div>
                        <div class="msg-bubble ai" style="color: var(--neon-cyan); font-style: italic;">
                            <i class="fas fa-circle-notch fa-spin" style="margin-right: 6px;"></i> Đang phân tích dữ liệu...
                        </div>
                    </div>`;
                
                chatHistoryEl.scrollTop = chatHistoryEl.scrollHeight;
            }
        });
    </script>
</body>
</html>