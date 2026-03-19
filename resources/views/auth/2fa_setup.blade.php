<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kích hoạt 2FA - (Server Health Monitoring & Detection System)</title>
    <style>
        :root {
            --bg-main: #0b1120;
            --bg-card: #111827;
            --border-color: #1f2937;
            --text-main: #f3f4f6;
            --neon-blue: #3b82f6;
            --neon-green: #22c55e;
            --error-red: #ef4444;
        }

        body { 
            margin: 0; 
            background-color: var(--bg-main); 
            color: var(--text-main); 
            font-family: 'Inter', 'Segoe UI', sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }

        .setup-container {
            background-color: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: 20px;
            padding: 40px;
            width: 90%;
            max-width: 420px;
            text-align: center;
            box-shadow: 0 20px 50px rgba(0,0,0,0.6);
        }

        h2 { 
            color: var(--neon-blue); 
            letter-spacing: 2px; 
            margin-bottom: 10px;
            text-transform: uppercase;
        }

        .description { 
            font-size: 14px; 
            color: #9ca3af; 
            margin-bottom: 25px; 
            line-height: 1.6; 
        }

        .qr-wrapper {
            background: white; /* Bắt buộc nền trắng để app dễ quét */
            padding: 15px;
            border-radius: 12px;
            display: inline-block;
            margin-bottom: 20px;
            border: 3px solid var(--neon-blue);
            transition: transform 0.3s ease;
        }
        
        .qr-wrapper:hover {
            transform: scale(1.02);
        }

        .manual-key {
            background: #1f2937;
            padding: 10px;
            border-radius: 8px;
            margin-bottom: 25px;
            font-family: monospace;
            font-size: 13px;
        }

        .manual-key span {
            display: block;
            color: var(--neon-blue);
            font-size: 11px;
            margin-bottom: 5px;
            text-transform: uppercase;
        }

        .input-group { margin-bottom: 20px; text-align: left; }
        
        label { 
            display: block; 
            font-size: 12px; 
            margin-bottom: 10px; 
            color: var(--neon-blue); 
            font-weight: bold;
            text-transform: uppercase;
        }
        
        input {
            width: 100%;
            padding: 14px;
            background: #0b1120;
            border: 1px solid var(--border-color);
            border-radius: 10px;
            color: white;
            font-size: 20px;
            text-align: center;
            letter-spacing: 8px;
            box-sizing: border-box;
            transition: 0.3s;
        }

        input:focus {
            outline: none;
            border-color: var(--neon-blue);
            box-shadow: 0 0 10px rgba(59, 130, 246, 0.3);
        }

        .alert-error {
            background: rgba(239, 68, 68, 0.1);
            color: var(--error-red);
            padding: 10px;
            border-radius: 8px;
            font-size: 13px;
            margin-bottom: 20px;
            border: 1px solid var(--error-red);
        }

        button {
            width: 100%;
            padding: 15px;
            background: var(--neon-blue);
            border: none;
            border-radius: 10px;
            color: white;
            font-weight: 800;
            cursor: pointer;
            transition: 0.3s;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        button:hover {
            background: #2563eb;
            box-shadow: 0 0 20px rgba(59, 130, 246, 0.5);
            transform: translateY(-2px);
        }

        .footer-link {
            margin-top: 20px;
            font-size: 12px;
            color: #4b5563;
        }
    </style>
</head>
<body>

    <div class="setup-container">
        <h2>Thiết lập 2FA</h2>
        <p class="description">Tăng cường bảo mật cho <b>Server Health Monitor</b> bằng cách quét mã QR qua ứng dụng Authenticator.</p>

        @if (session('error'))
            <div class="alert-error">
                {{ session('error') }}
            </div>
        @endif

        <div class="qr-wrapper">
            {{-- Render mã QR dạng SVG --}}
            {!! $qrCodeSvg !!}
        </div>

        <div class="manual-key">
            <span>Mã bí mật (Nhập thủ công)</span>
            <strong>{{ $secret }}</strong>
        </div>

        <form action="{{ route('2fa.enable') }}" method="POST">
            @csrf
            <div class="input-group">
                <label>Nhập mã xác nhận 6 số</label>
                <input type="text" name="verify_code" 
                       placeholder="······" 
                       maxlength="6" 
                       pattern="\d{6}" 
                       required 
                       autofocus 
                       autocomplete="off">
            </div>

            <button type="submit">Kích hoạt bảo mật</button>
        </form>

        <div class="footer-link">
            &copy; 2026 Server Health Monitor
        </div>
    </div>

</body>
</html>