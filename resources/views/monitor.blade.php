<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hệ thống Giám sát Máy chủ</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <meta http-equiv="refresh" content="5"> 

    <style>
        body { background-color: #f0f2f5; font-family: 'Segoe UI', sans-serif; }
        .dashboard-header { margin-top: 50px; margin-bottom: 40px; text-align: center; }
        
        /* Thiết kế các thẻ hiển thị thông số (Card) */
        .metric-card {
            border: none;
            border-radius: 15px;
            padding: 30px;
            color: white;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            transition: transform 0.2s;
        }
        .metric-card:hover { transform: translateY(-5px); } /* Hiệu ứng bay lên khi di chuột */
        
        .bg-gradient-cpu { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
        .bg-gradient-ram { background: linear-gradient(135deg, #2af598 0%, #009efd 100%); }
        .bg-gradient-disk { background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%); }

        .metric-value { font-size: 3.5rem; font-weight: bold; margin: 10px 0; }
        .metric-label { font-size: 1.1rem; text-transform: uppercase; letter-spacing: 1px; opacity: 0.9; }
        
        /* Phần hiển thị cảnh báo (Detection Area) */
        .alert-area { margin-bottom: 30px; }
        .status-badge { font-size: 0.9rem; padding: 5px 15px; border-radius: 20px; background: rgba(255,255,255,0.2); }
    </style>
</head>
<body>
    <div class="container">
        <div class="dashboard-header">
            <h2 class="fw-bold text-dark">🚀 SERVER HEALTH MONITORING</h2>
            <p class="text-muted">Hệ thống giám sát và phát hiện sự cố thời gian thực</p>
        </div>

        <div class="alert-area">
            @if(count($warnings) > 0)
                <div class="alert alert-danger shadow-sm">
                    <h4 class="alert-heading fw-bold">⚠️ PHÁT HIỆN BẤT THƯỜNG!</h4>
                    <hr>
                    <ul class="mb-0">
                        @foreach($warnings as $warning)
                            <li>{{ $warning }}</li>
                        @endforeach
                    </ul>
                </div>
            @else
                <div class="alert alert-success shadow-sm text-center">
                    <h5 class="mb-0">✅ Hệ thống đang hoạt động ổn định (Safe Status)</h5>
                </div>
            @endif
        </div>

        <div class="row g-4">
            <div class="col-md-4">
                <div class="metric-card bg-gradient-cpu text-center">
                    <div class="metric-label">CPU Load</div>
                    <div class="metric-value">{{ $cpu }}%</div>
                    <span class="status-badge">Processor</span>
                </div>
            </div>

            <div class="col-md-4">
                <div class="metric-card bg-gradient-ram text-center">
                    <div class="metric-label">RAM Usage</div>
                    <div class="metric-value">{{ $ram }}%</div>
                    <span class="status-badge">Total: {{ $total_ram }}</span>
                </div>
            </div>

            <div class="col-md-4">
                <div class="metric-card bg-gradient-disk text-center">
                    <div class="metric-label">Disk Usage</div>
                    <div class="metric-value">{{ $disk }}%</div>
                    <span class="status-badge">Storage</span>
                </div>
            </div>
        </div>
        
        <p class="text-center mt-5 text-muted small">
            Dữ liệu được cập nhật tự động mỗi 5 giây.<br>
            Server IP: {{ $server_ip }}
        </p>
    </div>
</body>
</html>