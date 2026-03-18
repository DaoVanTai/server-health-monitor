<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use PragmaRX\Google2FALaravel\Support\Authenticator;
use App\Models\User;

class SecurityController extends Controller
{
    /**
     * Bước 1: Hiển thị trang thiết lập 2FA và mã QR
     */
    /**
     * Bước 1: Hiển thị trang thiết lập 2FA và mã QR
     */
    public function show2faForm(\Illuminate\Http\Request $request)
    {
        $user = auth()->user();
        $google2fa = app('pragmarx.google2fa');

        // Nếu User chưa có Secret Key trong table, hãy tạo mới và lưu lại
        if (!$user->google2fa_secret) {
            $user->google2fa_secret = $google2fa->generateSecretKey();
            $user->save();
        }

        // TẠO MÃ QR DẠNG ẢNH SVG (Dùng getQRCodeInline thay vì getQRCodeUrl)
        $qrCodeSvg = $google2fa->getQRCodeInline(
            'Server Health Monitor', // Tên ngắn gọn sẽ hiện trên app Google Authenticator của điện thoại
            $user->email,
            $user->google2fa_secret
        );

        // Gửi biến $qrCodeSvg sang cho file Blade
        return view('auth.2fa_setup', [
            'qrCodeSvg' => $qrCodeSvg, // Tên biến này phải khớp với file blade
            'secret' => $user->google2fa_secret
        ]);
    }

    /**
     * Bước 2: Xác nhận mã từ điện thoại để chính thức kích hoạt 2FA
     */
    public function enable2fa(Request $request)
    {
        $user = auth()->user();
        $google2fa = app('pragmarx.google2fa');

        // Kiểm tra mã 6 số người dùng nhập vào
        $secret = $request->input('verify_code');
        $valid = $google2fa->verifyKey($user->google2fa_secret, $secret);

        if ($valid) {
            $user->google2fa_enabled = true;
            $user->save();
            return redirect()->route('monitor')->with('success', '2FA đã được kích hoạt thành công!');
        }

        return redirect()->back()->with('error', 'Mã xác nhận không đúng, vui lòng thử lại.');
    }

    /**
     * Bước 3: Trang nhập mã 2FA mỗi khi đăng nhập mới (Challenge)
     */
    public function verify2fa(Request $request)
    {
        return view('auth.2fa_verify');
    }
    // Thêm hàm này vào SecurityController
public function postVerify2fa(Request $request)
{
    // Thư viện sẽ tự động kiểm tra mã one_time_password người dùng gửi lên
    $authenticator = app(Authenticator::class)->boot($request);

    if ($authenticator->isAuthenticated()) {
        // Nếu đúng mã, cho vào Dashboard
        return redirect()->intended(route('monitor'));
    }

    // Nếu sai mã, quay lại trang nhập mã với thông báo lỗi
    return redirect()->back()->withErrors(['message' => 'Mã xác nhận không chính xác.']);
}
}