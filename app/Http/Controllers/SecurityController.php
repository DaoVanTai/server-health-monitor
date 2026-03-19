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
    public function show2faForm(Request $request)
    {
        $user = auth()->user();
        $google2fa = app('pragmarx.google2fa');

        // [TỐI ƯU BẢO MẬT]: Lưu tạm mã Secret vào Session thay vì Database
        // Để tránh trường hợp user chưa quét QR mà đã thoát trang
        $secret = $request->session()->get('2fa_setup_secret');

        if (!$secret) {
            $secret = $google2fa->generateSecretKey();
            $request->session()->put('2fa_setup_secret', $secret);
        }

        // Tạo mã QR dạng ảnh SVG
        $qrCodeSvg = $google2fa->getQRCodeInline(
            'Server Health Monitor', // Tên dự án
            $user->email,
            $secret
        );

        return view('auth.2fa_setup', [
            'qrCodeSvg' => $qrCodeSvg,
            'secret' => $secret
        ]);
    }

    /**
     * Bước 2: Xác nhận mã từ điện thoại để chính thức kích hoạt 2FA
     */
    public function enable2fa(Request $request)
    {
        $user = auth()->user();
        $google2fa = app('pragmarx.google2fa');

        // Lấy lại mã Secret đang lưu tạm trong Session
        $secret = $request->session()->get('2fa_setup_secret');
        $verifyCode = $request->input('verify_code');

        // Kiểm tra xem mã 6 số nhập vào có khớp với mã QR không
        $valid = $google2fa->verifyKey($secret, $verifyCode);

        if ($valid) {
            // [THÀNH CÔNG]: Lúc này mới CHÍNH THỨC lưu vào Database
            $user->google2fa_secret = $secret;
            $user->save();

            // Xóa bộ nhớ tạm
            $request->session()->forget('2fa_setup_secret');

            return redirect()->route('monitor')->with('success', '2FA đã được kích hoạt thành công!');
        }

        // [THẤT BẠI]: Nhập sai mã 6 số
        return redirect()->back()->with('error', 'Mã xác nhận không đúng, vui lòng thử lại.');
    }

    /**
     * Bước 3: Trang nhập mã 2FA mỗi khi đăng nhập mới
     */
    public function verify2fa(Request $request)
    {
        return view('auth.2fa_verify');
    }

    /**
     * Bước 4: Xử lý kiểm tra mã khi đăng nhập
     */
    public function postVerify2fa(Request $request)
    {
        // Lưu ý: Input name ở giao diện (file blade) BẮT BUỘC phải đặt là name="one_time_password"
        // vì thư viện Authenticator mặc định sẽ tìm cái tên này.
        $authenticator = app(Authenticator::class)->boot($request);

        if ($authenticator->isAuthenticated()) {
            return redirect()->intended(route('monitor'));
        }

        return redirect()->back()->withErrors(['message' => 'Mã xác nhận không chính xác.']);
    }
}