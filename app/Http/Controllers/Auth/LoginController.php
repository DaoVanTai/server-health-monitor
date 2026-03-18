<?php
namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

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
            return redirect()->intended('/'); // Chuyển hướng về Dashboard sau khi đăng nhập
        }

        return back()->withErrors([
            'email' => 'Thông tin đăng nhập không chính xác.',
        ]);
    }

    public function logout(Request $request) {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect('/login');
    }
    protected function authenticated(Request $request, $user)
{
    // Nếu chưa thiết lập 2FA (Secret rỗng), bắt đi cài đặt ngay
    if (empty($user->google2fa_secret)) {
        return redirect()->route('2fa.setup');
    }

    // Nếu đã có Secret nhưng chưa xác thực (Challenge), 
    // Middleware '2fa' ở trang Dashboard sẽ tự tóm bạn lại.
    return redirect()->intended($this->redirectPath());
}
}