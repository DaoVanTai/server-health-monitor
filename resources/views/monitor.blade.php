<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Server Health Monitor</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        :root {
            --bg-main: #0b1120;
            --bg-card: #111827;
            --border-color: #1f2937;
            --text-main: #f3f4f6;
            --text-muted: #9ca3af;
            
            /* Neon Colors */
            --neon-purple: #a855f7;
            --neon-blue: #3b82f6;
            --neon-green: #22c55e;
            --neon-red: #ef4444;
        }

        body {
            margin: 0;
            padding: 0;
            background-color: var(--bg-main);
            color: var(--text-main);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            display: flex;
            min-height: 100vh;
        }

        /* Sidebar Mockup */
        .sidebar {
            width: 60px;
            background-color: #0f172a;
            border-right: 1px solid var(--border-color);
            display: flex;
            flex-direction: column;
            align-items: center;
            padding-top: 20px;
            gap: 20px;
        }

        .sidebar-item { width: 24px; height: 24px; background-color: var(--border-color); border-radius: 4px; opacity: 0.5; }
        .sidebar-item.active { background-color: var(--neon-blue); opacity: 1; }

        /* Main Content */
        .main-content {
            flex: 1;
            padding: 30px 40px;
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        /* Header Area */
        .header { display: flex; justify-content: space-between; align-items: flex-start; }
        .title-area h1 { font-size: 28px; letter-spacing: 2px; margin: 0 0 5px 0; text-transform: uppercase; }
        .title-area p { color: var(--text-muted); margin: 0; font-size: 14px; }
        .top-right-stats { display: flex; gap: 20px; font-size: 12px; color: var(--text-muted); text-align: right; }
        .info-row { font-size: 12px; color: var(--text-muted); border-bottom: 1px solid var(--border-color); padding-bottom: 15px; }

        /* Cảnh báo tấn công */
        #attack-warning-banner {
            display: none; background-color: rgba(239, 68, 68, 0.1); border: 1px solid var(--neon-red);
            color: var(--neon-red); padding: 15px 20px; border-radius: 6px; font-weight: bold;
            text-align: center; letter-spacing: 1px; text-transform: uppercase;
            animation: pulse-red 1.5s infinite;
        }

        @keyframes pulse-red {
            0% { box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.4); }
            70% { box-shadow: 0 0 0 10px rgba(239, 68, 68, 0); }
            100% { box-shadow: 0 0 0 0 rgba(239, 68, 68, 0); }
        }

        /* Status Bar */
        #system-status-bar {
            display: flex; justify-content: flex-start; gap: 15px; align-items: center;
            background-color: rgba(34, 197, 94, 0.1); border: 1px solid rgba(34, 197, 94, 0.3);
            color: var(--neon-green); padding: 12px 20px; border-radius: 6px; font-weight: bold; font-size: 14px; transition: all 0.3s ease;
        }

        /* Metric Cards Grid */
        .cards-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 25px; }
        .metric-card {
            background-color: var(--bg-card); border-radius: 12px; padding: 20px; display: flex;
            flex-direction: column; gap: 15px; position: relative; transition: transform 0.3s ease, box-shadow 0.3s ease; cursor: pointer;
        }
        .metric-card:hover { transform: translateY(-8px); z-index: 10; }

        .card-purple { border: 1px solid var(--neon-purple); box-shadow: inset 0 0 10px rgba(168, 85, 247, 0.05); }
        .card-purple:hover { box-shadow: 0 8px 25px rgba(168, 85, 247, 0.3), inset 0 0 15px rgba(168, 85, 247, 0.1); }

        .card-blue { border: 1px solid var(--neon-blue); box-shadow: inset 0 0 10px rgba(59, 130, 246, 0.05); }
        .card-blue:hover { box-shadow: 0 8px 25px rgba(59, 130, 246, 0.3), inset 0 0 15px rgba(59, 130, 246, 0.1); }

        .card-green { border: 1px solid var(--neon-green); box-shadow: inset 0 0 10px rgba(34, 197, 94, 0.05); }
        .card-green:hover { box-shadow: 0 8px 25px rgba(34, 197, 94, 0.3), inset 0 0 15px rgba(34, 197, 94, 0.1); }

        .card-header { display: flex; justify-content: space-between; font-weight: bold; font-size: 14px; letter-spacing: 1px; }
        .card-content { display: flex; justify-content: space-between; align-items: center; }
        
        /* Thay thế Graphic bằng Text nghệ thuật */
        .graphic-text { font-size: 24px; font-weight: 900; opacity: 0.1; letter-spacing: -2px; }

        .value-area { text-align: right; }
        .main-value { font-size: 36px; font-weight: bold; margin: 0; line-height: 1.2; }
        .card-purple .main-value { color: var(--neon-purple); text-shadow: 0 0 10px rgba(168, 85, 247, 0.5); }
        .card-blue .main-value { color: var(--neon-blue); text-shadow: 0 0 10px rgba(59, 130, 246, 0.5); }
        .card-green .main-value { color: var(--neon-green); text-shadow: 0 0 10px rgba(34, 197, 94, 0.5); }
        .sub-value { font-size: 12px; color: var(--text-muted); margin: 0; }

        /* Progress Bars */
        .css-progress-track { height: 8px; background-color: rgba(255,255,255,0.1); border-radius: 4px; overflow: hidden; width: 100%; }
        .css-progress-fill { height: 100%; transition: width 0.5s ease-in-out; width: 0%; }
        #cpu-bar { background-color: var(--neon-purple); box-shadow: 0 0 5px var(--neon-purple); }
        #ram-bar { background-color: var(--neon-blue); box-shadow: 0 0 5px var(--neon-blue); }
        #disk-bar { background-color: var(--neon-green); box-shadow: 0 0 5px var(--neon-green); }
        .card-footer { display: flex; justify-content: space-between; font-size: 11px; color: var(--text-muted); border-top: 1px solid rgba(255,255,255,0.05); padding-top: 10px; }

        /* History Chart Area */
        .chart-section {
            background-color: var(--bg-card); border: 1px solid var(--border-color); border-radius: 12px; padding: 20px;
            flex: 1; display: flex; flex-direction: column; min-height: 250px;
        }
        .chart-header { display: flex; justify-content: space-between; font-size: 14px; font-weight: bold; margin-bottom: 20px; }
        .chart-legend { display: flex; gap: 15px; font-size: 12px; }
        .legend-item { display: flex; align-items: center; gap: 5px; }
        .dot { width: 8px; height: 8px; border-radius: 50%; }
        .dot.purple { background-color: var(--neon-purple); box-shadow: 0 0 5px var(--neon-purple);}
        .dot.blue { background-color: var(--neon-blue); box-shadow: 0 0 5px var(--neon-blue);}
        
        .chart-area { flex: 1; position: relative; }
        canvas { width: 100% !important; height: 100% !important; }

        /* Footer */
        .dashboard-footer { text-align: right; font-size: 11px; color: var(--text-muted); margin-top: 10px; }
    </style>
</head>
<body>

    <aside class="sidebar">
        <div class="sidebar-item"></div><div class="sidebar-item active"></div>
        <div class="sidebar-item"></div><div class="sidebar-item"></div><div class="sidebar-item"></div>
    </aside>

    <main class="main-content">
        <header class="header">
            <div class="title-area">
                <h1>SERVER HEALTH MONITOR</h1>
                <p>Real-time Server Monitoring and Detection System</p>
            </div>
            <div class="top-right-stats">
                <div><div>CORES</div><strong style="color: white;">LIVE</strong></div>
                <div><div>NETWORK</div><strong style="color: white;">SECURE</strong></div>
            </div>
        </header>

        <div class="info-row">V1.0.9 // SYS.CORE // Project: Server Health Monitor // Ubuntu Linux</div>

        <div id="attack-warning-banner">[!] CẢNH BÁO MỨC ĐỘ CRITICAL: HỆ THỐNG QUÁ TẢI HOẶC PHÁT HIỆN TẤN CÔNG [!]</div>

        <div id="system-status-bar">
            <div style="width: 12px; height: 12px; background: currentColor; border-radius: 2px;"></div>
            <span id="status-text">Hệ thống đang hoạt động ổn định (Safe Status)</span>
        </div>

        <div class="cards-grid">
            <div class="metric-card card-purple">
                <div class="card-header"><span>CPU METRICS</span></div>
                <div class="card-content">
                    <div class="graphic-text" style="color: var(--neon-purple);">CPU</div>
                    <div class="value-area">
                        <p class="main-value"><span id="cpu-val">0.00</span><span style="font-size: 20px;">%</span></p>
                        <p class="sub-value">CPU LOAD</p>
                    </div>
                </div>
                <div class="css-progress-track"><div id="cpu-bar" class="css-progress-fill"></div></div>
                <div class="card-footer"><div>Type: Processor</div><div style="text-align: right;">Real-time Data</div></div>
            </div>

            <div class="metric-card card-blue">
                <div class="card-header"><span>RAM UTILIZATION</span></div>
                <div class="card-content">
                    <div class="graphic-text" style="color: var(--neon-blue);">RAM</div>
                    <div class="value-area">
                        <p class="main-value"><span id="ram-val">0.00</span><span style="font-size: 20px;">%</span></p>
                        <p class="sub-value">RAM USAGE</p>
                    </div>
                </div>
                <div class="css-progress-track"><div id="ram-bar" class="css-progress-fill"></div></div>
                <div class="card-footer"><div>Total: <span id="ram-total">0</span> GB</div><div style="text-align: right;">Used: <span id="ram-used">0</span> GB</div></div>
            </div>

            <div class="metric-card card-green">
                <div class="card-header"><span>DISK CAPACITY</span></div>
                <div class="card-content">
                    <div class="graphic-text" style="color: var(--neon-green);">DSK</div>
                    <div class="value-area">
                        <p class="main-value"><span id="disk-val">0.00</span><span style="font-size: 20px;">%</span></p>
                        <p class="sub-value">DISK USAGE</p>
                    </div>
                </div>
                <div class="css-progress-track"><div id="disk-bar" class="css-progress-fill"></div></div>
                <div class="card-footer"><div>Storage (/)</div><div style="text-align: right;">Free: <span id="disk-free">0</span> GB</div></div>
            </div>
        </div>

        <div class="chart-section">
            <div class="chart-header">
                <span>CORE METRICS HISTORY (60s)</span>
                <div class="chart-legend">
                    <div class="legend-item"><div class="dot purple"></div> CPU</div>
                    <div class="legend-item"><div class="dot blue"></div> RAM</div>
                </div>
            </div>
            <div class="chart-area">
                <canvas id="historyChart"></canvas>
            </div>
        </div>

        <div class="dashboard-footer">
            Dữ liệu được cập nhật tự động mỗi 5 giây.<br>
            Cập nhật lần cuối: <span id="last-update-time">--:--:--</span>
        </div>
    </main>

    <script>
        // CẤU HÌNH BIỂU ĐỒ (CHART.JS)
        const ctx = document.getElementById('historyChart').getContext('2d');
        
        // Tạo dải màu gradient từ trên xuống dưới cho giống ảnh
        let gradientPurple = ctx.createLinearGradient(0, 0, 0, 300);
        gradientPurple.addColorStop(0, 'rgba(168, 85, 247, 0.4)');
        gradientPurple.addColorStop(1, 'rgba(168, 85, 247, 0.0)');

        let gradientBlue = ctx.createLinearGradient(0, 0, 0, 300);
        gradientBlue.addColorStop(0, 'rgba(59, 130, 246, 0.4)');
        gradientBlue.addColorStop(1, 'rgba(59, 130, 246, 0.0)');

        // Mảng lưu trữ dữ liệu (tối đa 12 điểm = 60s)
        let timeLabels = [];
        let cpuDataPoints = [];
        let ramDataPoints = [];

        const historyChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: timeLabels,
                datasets: [
                    {
                        label: 'CPU',
                        data: cpuDataPoints,
                        borderColor: '#a855f7',
                        backgroundColor: gradientPurple,
                        borderWidth: 2,
                        tension: 0.4, // Tạo đường cong mượt mà
                        fill: true,   // Đổ màu mờ ở dưới
                        pointRadius: 0 // Ẩn các chấm tròn
                    },
                    {
                        label: 'RAM',
                        data: ramDataPoints,
                        borderColor: '#3b82f6',
                        backgroundColor: gradientBlue,
                        borderWidth: 2,
                        tension: 0.4,
                        fill: true,
                        pointRadius: 0
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                animation: { duration: 500 }, // Hiệu ứng chạy từ từ
                scales: {
                    y: {
                        beginAtZero: true,
                        max: 100, // Thang đo từ 0 đến 100%
                        grid: { color: 'rgba(255,255,255,0.05)' },
                        ticks: { color: '#9ca3af' }
                    },
                    x: {
                        grid: { color: 'rgba(255,255,255,0.05)' },
                        ticks: { color: '#9ca3af' }
                    }
                },
                plugins: { legend: { display: false } } // Đã có chú thích HTML ở trên nên ẩn cái mặc định
            }
        });

        // HÀM LẤY DỮ LIỆU TỪ MÁY CHỦ
        function fetchRealServerData() {
            fetch('/api/server-status')
                .then(response => response.json())
                .then(data => {
                    // 1. Cập nhật thẻ số liệu tĩnh
                    document.getElementById('cpu-val').innerText = data.cpu_percent;
                    document.getElementById('ram-val').innerText = data.ram_percent;
                    document.getElementById('ram-total').innerText = data.ram_total;
                    document.getElementById('ram-used').innerText = data.ram_used;
                    document.getElementById('disk-val').innerText = data.disk_percent;
                    document.getElementById('disk-free').innerText = data.disk_free;

                    // 2. Cập nhật thanh tiến trình chạy ngang
                    document.getElementById('cpu-bar').style.width = data.cpu_percent + '%';
                    document.getElementById('ram-bar').style.width = data.ram_percent + '%';
                    document.getElementById('disk-bar').style.width = data.disk_percent + '%';

                    // 3. Xử lý Cảnh báo Tấn công / Quá tải
                    let warningBanner = document.getElementById('attack-warning-banner');
                    let statusBar = document.getElementById('system-status-bar');
                    let statusText = document.getElementById('status-text');

                    if (data.is_attacked) {
                        warningBanner.style.display = 'block';
                        statusBar.style.backgroundColor = 'rgba(239, 68, 68, 0.1)';
                        statusBar.style.borderColor = 'var(--neon-red)';
                        statusBar.style.color = 'var(--neon-red)';
                        statusText.innerText = 'CẢNH BÁO: HỆ THỐNG QUÁ TẢI HOẶC ĐANG BỊ TẤN CÔNG!';
                    } else {
                        warningBanner.style.display = 'none';
                        statusBar.style.backgroundColor = 'rgba(34, 197, 94, 0.1)';
                        statusBar.style.borderColor = 'rgba(34, 197, 94, 0.3)';
                        statusBar.style.color = 'var(--neon-green)';
                        statusText.innerText = 'Hệ thống đang hoạt động ổn định (Safe Status)';
                    }

                    // 4. CẬP NHẬT BIỂU ĐỒ ĐỘNG
                    let now = new Date();
                    let timeString = now.toLocaleTimeString();
                    document.getElementById('last-update-time').innerText = timeString;

                    // Thêm dữ liệu mới vào mảng
                    timeLabels.push(timeString);
                    cpuDataPoints.push(data.cpu_percent);
                    ramDataPoints.push(data.ram_percent);

                    // Nếu mảng dài hơn 12 điểm (60 giây), xóa điểm cũ nhất
                    if (timeLabels.length > 12) {
                        timeLabels.shift();
                        cpuDataPoints.shift();
                        ramDataPoints.shift();
                    }

                    // Ra lệnh cho biểu đồ vẽ lại
                    historyChart.update();
                })
                .catch(error => {
                    console.error('Lỗi khi lấy dữ liệu server:', error);
                });
        }

        // Chạy lần đầu khi load trang
        fetchRealServerData();

        // Chạy lặp lại mỗi 5 giây
        setInterval(fetchRealServerData, 5000);
    </script>
</body>
</html>