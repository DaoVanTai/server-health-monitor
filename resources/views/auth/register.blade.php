<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - SYS Monitor</title>
    <style>
        :root {
            --bg-main: #0b1120;
            --bg-card: #111827;
            --border-color: #1f2937;
            --neon-blue: #3b82f6;
            --text-main: #f3f4f6;
        }

        body {
            margin: 0;
            background-color: var(--bg-main);
            color: var(--text-main);
            font-family: 'Segoe UI', sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }

        .auth-card {
            background-color: var(--bg-card);
            padding: 40px;
            border-radius: 12px;
            border: 1px solid var(--neon-blue);
            box-shadow: 0 0 20px rgba(59, 130, 246, 0.2);
            width: 100%;
            max-width: 400px;
        }

        h2 { text-align: center; text-transform: uppercase; letter-spacing: 2px; color: var(--neon-blue); }

        .form-group { margin-bottom: 20px; }
        label { display: block; margin-bottom: 8px; font-size: 14px; color: #9ca3af; }
        
        input {
            width: 100%;
            padding: 12px;
            background: #0b1120;
            border: 1px solid var(--border-color);
            border-radius: 6px;
            color: white;
            box-sizing: border-box;
            outline: none;
        }

        input:focus { border-color: var(--neon-blue); box-shadow: 0 0 10px rgba(59, 130, 246, 0.3); }

        button {
            width: 100%;
            padding: 12px;
            background: var(--neon-blue);
            border: none;
            border-radius: 6px;
            color: white;
            font-weight: bold;
            cursor: pointer;
            margin-top: 10px;
            transition: 0.3s;
        }

        button:hover { transform: scale(1.02); box-shadow: 0 0 15px var(--neon-blue); }

        .link { text-align: center; margin-top: 20px; font-size: 13px; color: #9ca3af; }
        .link a { color: var(--neon-blue); text-decoration: none; }
    </style>
</head>
<body>
    <div class="auth-card">
        <h2>Đăng ký hệ thống</h2>
        <form action="{{ route('register') }}" method="POST">
            @csrf
            <div class="form-group">
                <label>Tên Admin</label>
                <input type="text" name="name" required placeholder="Nhập tên của bạn">
            </div>
            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" required placeholder="admin@server.com">
            </div>
            <div class="form-group">
                <label>Mật khẩu</label>
                <input type="password" name="password" required placeholder="••••••••">
            </div>
            <div class="form-group">
                <label>Xác nhận mật khẩu</label>
                <input type="password" name="password_confirmation" required placeholder="••••••••">
            </div>
            <button type="submit">TẠO TÀI KHOẢN</button>
        </form>
        <div class="link">Đã có tài khoản? <a href="/login">Đăng nhập ngay</a></div>

        @if ($errors->any())
    <div style="color: #ef4444; font-size: 13px; margin-bottom: 15px; background: rgba(239, 68, 68, 0.1); padding: 10px; border-radius: 6px;">
        <ul>
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif
    </div>
</body>
</html>