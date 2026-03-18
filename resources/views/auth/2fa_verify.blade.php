<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Xác thực bảo mật - Server Health Monitor</title>
    <style>
        :root {
            --bg-main: #0b1120;
            --bg-card: #111827;
            --border-color: #1f2937;
            --text-main: #f3f4f6;
            --neon-blue: #3b82f6;
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

        .verify-container {
            background-color: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: 20px;
            padding: 40px;
            width: 90%;
            max-width: 400px;
            text-align: center;
            box-shadow: 0 20px 50px rgba(0,0,0,0.6);
        }

        .shield-icon {
            font-size: 48px;
            margin-bottom: 20px;
            display: inline-block;
            filter: drop-shadow(0 0 10px rgba(59, 130, 246, 0.5));
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
            margin-bottom: 30px; 
            line-height: 1.6; 
        }

        .input-group { margin-bottom: 25px; text-align: left; }
        
        input {
            width: 100%;
            padding: 16px;
            background: #0b1120;
            border: 1px solid var(--border-color);
            border-radius: 12px;
            color: white;
            font-size: 24px;
            text-align: center;
            letter-spacing: 12px;
            box-sizing: border-box;
            transition: 0.3s;
        }

        input:focus {
            outline: none;
            border-color: var(--neon-blue);
            box-shadow: 0 0 15px rgba(59, 130, 246, 0.4);
        }

        .alert-error {
            background: rgba(239, 68, 68, 0.1);
            color: var(--error-red);
            padding: 12px;
            border-radius: 8px;
            font-size: 13px;
            margin-bottom: 20px;
            border: 1px solid var(--error-red);
        }

        button {
            width: 100%;
            padding: 16px;
            background: var(--neon-blue);
            border: none;
            border-radius: 12px;
            color: white;
            font-weight: 800;
            font-size: 16px;
            cursor: pointer;
            transition: 0.3s;
            text-transform: uppercase;
            letter-spacing: 2px;
        }

        button:hover {
            background: #2563eb;
            box-shadow: 0 0 25px rgba(59, 130, 246, 0.6);
            transform: translateY(-2px);
        }
    </style>
</head>
<body>

    <div class="verify-container">
        <div class="shield-icon">🛡️</div>
        <h2>XÁC THỰC DANH TÍNH</h2>
        <p class="description">Vui lòng mở ứng dụng Authenticator và nhập mã 6 số để truy cập Server Monitor.</p>

        {{-- Bắt lỗi từ thư viện trả về --}}
        @if ($errors->any())
            <div class="alert-error">
                Mã xác thực không chính xác hoặc đã hết hạn!
            </div>
        @endif

        {{-- Submit thẳng về route xác thực mà bạn đã định nghĩa --}}
        <form action="{{ route('2fa.postVerify') }}" method="POST">
            @csrf
            <div class="input-group">
                {{-- Tên input bắt buộc phải là one_time_password --}}
                <input type="text" name="one_time_password" 
                       placeholder="······" 
                       maxlength="6" 
                       pattern="\d{6}" 
                       required 
                       autofocus 
                       autocomplete="off">
            </div>

            <button type="submit">TRUY CẬP HỆ THỐNG</button>
        </form>
    </div>

</body>
</html>