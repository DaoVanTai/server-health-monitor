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
            --bg-sidebar: #0b1120;
            --neon-cyan: #22d3ee;
            --neon-purple: #a855f7;
            --text-main: #f3f4f6;
            --text-muted: #64748b;
            --border-color: #1e293b;
        }
        body { background: var(--bg-main); color: var(--text-main); font-family: 'Segoe UI', sans-serif; margin: 0; display: flex; min-height: 100vh; overflow-x: hidden; }
        
        ::-webkit-scrollbar { width: 6px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: #334155; border-radius: 10px; }
        ::-webkit-scrollbar-thumb:hover { background: var(--neon-cyan); }

        /* --- SIDEBAR CHÍNH MÀN HÌNH --- */
        .sidebar { 
            width: 70px; background-color: #020617; border-right: 1px solid var(--border-color); 
            display: flex; flex-direction: column; padding: 20px 0; position: fixed; 
            height: 100vh; z-index: 1000; transition: width 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            overflow: hidden; white-space: nowrap;
        }
        .sidebar:hover { width: 220px; box-shadow: 10px 0 30px rgba(0,0,0,0.5); }
        .sidebar-item { 
            width: 100%; padding: 15px 0; display: flex; align-items: center; 
            color: var(--text-muted); text-decoration: none; transition: all 0.2s; border-left: 3px solid transparent; 
        }
        .sidebar-icon-wrapper { min-width: 70px; display: flex; justify-content: center; align-items: center; font-size: 20px; }
        .sidebar-item span { opacity: 0; transform: translateX(-10px); transition: all 0.3s; font-size: 14px; font-weight: 500; }
        .sidebar:hover .sidebar-item span { opacity: 1; transform: translateX(0); }
        .sidebar-item.active { color: var(--neon-cyan); border-left: 3px solid var(--neon-cyan); background: rgba(34, 211, 238, 0.05); }
        .sidebar-item:hover { color: var(--text-main); }

        .main-content { margin-left: 70px; padding: 40px; width: 100%; box-sizing: border-box; }
        .ai-header { color: var(--neon-cyan); font-weight: bold; margin-bottom: 10px; display: flex; align-items: center; gap: 12px; text-shadow: 0 0 10px rgba(34, 211, 238, 0.3); }
        .ai-grid { display: grid; grid-template-columns: 0.8fr 1.5fr; gap: 25px; margin-top: 20px; align-items: start; }
        
        /* --- TERMINAL --- */
        .ai-terminal { 
            background: #000; border: 1px solid var(--border-color); border-radius: 12px; 
            padding: 25px; font-family: 'Courier New', monospace; 
            box-shadow: inset 0 0 20px rgba(0,0,0,1); height: 550px; position: relative; overflow-y: auto;
        }
        .ai-terminal::before {
            content: ""; position: absolute; top: 0; left: 0; width: 100%; height: 100%;
            background: linear-gradient(rgba(18, 16, 16, 0) 50%, rgba(0, 0, 0, 0.25) 50%), linear-gradient(90deg, rgba(255, 0, 0, 0.06), rgba(0, 255, 0, 0.02), rgba(0, 0, 255, 0.06));
            background-size: 100% 2px, 3px 100%; pointer-events: none;
        }
        .terminal-line { margin-bottom: 12px; line-height: 1.5; font-size: 14px; position: relative; z-index: 1; }
        .line-success { color: #22c55e; }
        .line-info { color: var(--text-muted); }
        .line-ai { color: var(--neon-cyan); }
        .scan-line { width: 100%; height: 2px; background: var(--neon-cyan); opacity: 0.3; position: absolute; top: 0; left: 0; animation: scan 4s linear infinite; z-index: 2; }
        @keyframes scan { 0% { top: 0; } 100% { top: 100%; } }

        /* --- CHAT WRAPPER --- */
        .chat-wrapper { 
            display: flex; height: 550px; background: var(--bg-card); 
            border: 1px solid var(--border-color); border-radius: 12px; overflow: hidden; 
            box-shadow: 0 10px 30px rgba(0,0,0,0.5); position: relative;
        }

        /* --- LỊCH SỬ CHAT --- */
        .chat-history-sidebar {
            width: 55px; background: var(--bg-sidebar); border-right: 1px solid var(--border-color);
            display: flex; flex-direction: column; transition: width 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative; z-index: 10;
        }
        .chat-history-sidebar:hover { width: 250px; box-shadow: 5px 0 15px rgba(0,0,0,0.3); }
        
        .sidebar-text-chat { opacity: 0; visibility: hidden; transition: all 0.2s; white-space: nowrap; margin-left: 10px; }
        .chat-history-sidebar:hover .sidebar-text-chat { opacity: 1; visibility: visible; }

        .new-chat-btn-container { padding: 10px; border-bottom: 1px solid var(--border-color); display: flex; justify-content: center; }
        .btn-new-chat {
            width: 100%; padding: 10px; background: transparent; border: 1px solid var(--neon-cyan); 
            color: var(--neon-cyan); border-radius: 6px; cursor: pointer; transition: 0.3s;
            font-size: 13px; font-weight: bold; display: flex; align-items: center; justify-content: center;
        }
        .btn-new-chat:hover { background: rgba(34, 211, 238, 0.1); box-shadow: 0 0 10px rgba(34, 211, 238, 0.2); }
        
        .session-list { flex: 1; overflow-y: auto; overflow-x: hidden; padding: 10px; display: flex; flex-direction: column; gap: 5px; }
        
        .session-item {
            display: flex; align-items: center; justify-content: space-between; padding: 12px 10px;
            color: var(--text-muted); border-radius: 6px; border-left: 3px solid transparent; transition: 0.2s;
        }
        .session-item:hover { background: rgba(255,255,255,0.05); color: var(--text-main); }
        .session-item.active { background: rgba(34, 211, 238, 0.08); color: var(--neon-cyan); border-left: 3px solid var(--neon-cyan); }
        
        .session-item-left { display: flex; align-items: center; flex: 1; overflow: hidden; cursor: pointer; }
        
        /* 3-DOTS MENU */
        .session-options { position: relative; }
        .session-options-btn { 
            background: transparent; border: none; color: var(--text-muted); 
            cursor: pointer; font-size: 16px; display: none; padding: 0 5px; font-weight: bold;
        }
        .session-options-btn:hover { color: var(--neon-cyan); }
        .chat-history-sidebar:hover .session-options-btn { display: block; }
        
        .session-dropdown {
            display: none; position: absolute; right: 0; top: 25px;
            background: var(--bg-card); border: 1px solid var(--border-color);
            border-radius: 6px; box-shadow: 0 5px 15px rgba(0,0,0,0.5); z-index: 100;
            flex-direction: column; min-width: 120px; overflow: hidden;
        }
        .session-dropdown.show { display: flex; }
        .dropdown-item { padding: 10px 15px; font-size: 13px; cursor: pointer; color: var(--text-main); white-space: nowrap; }
        .dropdown-item:hover { background: rgba(34, 211, 238, 0.1); color: var(--neon-cyan); }
        .dropdown-item.text-danger { color: #ef4444; }
        .dropdown-item.text-danger:hover { background: rgba(239, 68, 68, 0.1); color: #ef4444; }

        /* --- KHUNG CHAT CHÍNH --- */
        .chat-main { flex: 1; display: flex; flex-direction: column; background: var(--bg-card); min-width: 0; }
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
        
        .msg-bubble { max-width: 80%; padding: 12px 18px; font-size: 14px; line-height: 1.6; white-space: pre-wrap; }
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
        <a href="{{ route('monitor') }}" class="sidebar-item {{ Request::is('monitor*') ? 'active' : '' }}">
            <div class="sidebar-icon-wrapper">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M3 12a9 9 0 1 1 18 0M12 7v5l3 3"></path></svg>
            </div>
            <span>Dashboard</span>
        </a>
        <a