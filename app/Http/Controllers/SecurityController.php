<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use PragmaRX\Google2FALaravel\Support\Authenticator;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SecurityController extends Controller
{
    /* ========================================================
       PHẦN 1: BẢO MẬT XÁC THỰC 2 LỚP (2FA)
       ======================================================== */

    /**
     * Bước 1: Hiển thị trang thiết lập 2FA và mã QR
     */
    public function show2faForm(Request $request)
    {
        $user = auth()->user();
        $google2fa = app('pragmarx.google2fa');

        // [TỐI ƯU BẢO MẬT]: Lưu tạm mã Secret vào Session thay vì Database
        $secret = $request->session()->get('2fa_setup_secret');

        if (!$secret) {
            $secret = $google2fa->generateSecretKey();
            $request->session()->put('2fa_setup_secret', $secret);
        }

        // Tạo mã QR dạng ảnh SVG
        $qrCodeSvg = $google2fa->getQRCodeInline(
            '(Server Health Monitoring & Detection System)', // Tên dự án
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

        $secret = $request->session()->get('2fa_setup_secret');
        $verifyCode = $request->input('verify_code');

        $valid = $google2fa->verifyKey($secret, $verifyCode);

        if ($valid) {
            $user->google2fa_secret = $secret;
            $user->save();
            $request->session()->forget('2fa_setup_secret');

            return redirect()->route('monitor')->with('success', '2FA đã được kích hoạt thành công!');
        }

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
        $authenticator = app(Authenticator::class)->boot($request);

        if ($authenticator->isAuthenticated()) {
            return redirect()->intended(route('monitor'));
        }

        return redirect()->back()->withErrors(['message' => 'Mã xác nhận không chính xác.']);
    }

    /* ========================================================
       PHẦN 2: BẢO MẬT TƯỜNG LỬA (OS-LEVEL FIREWALL MITIGATION)
       ======================================================== */

    /**
     * Khóa một IP và phong ấn trên hệ điều hành
     */
    public function block(Request $request)
    {
        $request->validate([
            'ip_address' => 'required|ip',
            'reason' => 'nullable|string'
        ]);

        $ip = $request->input('ip_address');
        $reason = $request->input('reason', 'Manual Block từ Admin');

        // 1. Lưu vào Database để theo dõi trên giao diện Web
        DB::table('blacklists')->insert([
            'ip_address' => $ip,
            'reason' => $reason,
            'created_at' => now(),
            'updated_at' => now()
        ]);

        // 2. GỌI LỆNH TƯỜNG LỬA HỆ ĐIỀU HÀNH (OS-LEVEL)
        if (PHP_OS_FAMILY === 'Linux') {
            try {
                // Lệnh ufw deny chặn đứng IP này trên TẤT CẢ các cổng
                $command = "sudo ufw deny from " . escapeshellarg($ip);
                shell_exec($command);
            } catch (\Exception $e) {
                Log::error("Không thể thực thi lệnh Firewall OS (Block): " . $e->getMessage());
            }
        }

        return redirect()->back()->with('success', 'Đã phong ấn IP trên toàn bộ hệ thống!');
    }

    /**
     * Gỡ chặn một IP và mở khóa trên hệ điều hành
     */
    public function unblock($id)
    {
        $item = DB::table('blacklists')->find($id);

        if ($item) {
            $ip = $item->ip_address;

            // 1. Xóa khỏi Database
            DB::table('blacklists')->where('id', $id)->delete();

            // 2. Xóa quy tắc chặn trên tường lửa Hệ điều hành
            if (PHP_OS_FAMILY === 'Linux') {
                try {
                    $command = "sudo ufw delete deny from " . escapeshellarg($ip);
                    shell_exec($command);
                } catch (\Exception $e) {
                    Log::error("Không thể thực thi lệnh Firewall OS (Unblock): " . $e->getMessage());
                }
            }
        }

        return redirect()->back()->with('success', 'Đã gỡ phong ấn IP thành công!');
    }
}