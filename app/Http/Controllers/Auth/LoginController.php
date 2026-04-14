<?php
namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache; // BỔ SUNG: Khai báo thư viện Cache

class LoginController extends Controller
{
    public function showLoginForm() {
        return view('auth.login');
    }

    public function login(Request $request) {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if (Auth::attempt($credentials)) {
            $request->session()->regenerate();
            $user = Auth::user();

            // ==========================================
            // 1. TUYỆT CHIÊU: GHI NHỚ IP ADMIN VÀO CACHE
            // ==========================================
            $adminIp = $request->ip();
            $safeIps = Cache::get('admin_safe_ips', []);
            
            // Nếu IP này chưa có trong danh sách thì thêm vào
            if (!in_array($adminIp, $safeIps)) {
                $safeIps[] = $adminIp;
            }
            
            // Lưu lại danh sách này trong 24 giờ (1440 phút)
            Cache::put('admin_safe_ips', $safeIps, now()->addHours(24));

            // ==========================================
            // 2. KIỂM TRA LUỒNG 2FA CHUẨN XÁC
            // ==========================================
            // Nếu chưa thiết lập 2FA (Secret rỗng), bắt đi cài đặt ngay
            if (empty($user->google2fa_secret)) {
                return redirect()->route('2fa.setup');
            }

            // Nếu đã có 2FA, chuyển về Dashboard (Middleware 2FA sẽ lo phần còn lại)
            return redirect()->intended('/'); 
        }

        return back()->withErrors([
            'email' => 'Thông tin đăng nhập không chính xác.',
        ]);
    }

    public function logout(Request $request) {
        // ==========================================
        // DỌN DẸP: XÓA IP KHỎI CACHE KHI ĐĂNG XUẤT
        // ==========================================
        $adminIp = $request->ip();
        $safeIps = Cache::get('admin_safe_ips', []);
        
        // Lọc bỏ IP hiện tại ra khỏi mảng
        $safeIps = array_filter($safeIps, function($ip) use ($adminIp) {
            return $ip !== $adminIp;
        });
        Cache::put('admin_safe_ips', $safeIps, now()->addHours(24));

        // Tiến hành đăng xuất
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        
        return redirect('/login');
    }
}