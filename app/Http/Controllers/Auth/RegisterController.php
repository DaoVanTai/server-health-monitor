<?php
namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class RegisterController extends Controller
{
    public function showRegistrationForm() {
        return view('auth.register');
    }

    public function register(Request $request) {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
        ]);

        User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        return redirect('/login')->with('success', 'Đăng ký thành công! Hãy đăng nhập.');
    }
    protected function registered(\Illuminate\Http\Request $request, $user)
{
    // Vừa đăng ký xong thì chắc chắn chưa có 2FA, 
    // chặn lại không cho vào thẳng Dashboard mà bắt đi quét QR ngay!
    return redirect()->route('2fa.setup');
}
}