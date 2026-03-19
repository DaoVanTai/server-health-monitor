<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class CreateAdminUser extends Command
{
    // Tên câu lệnh bạn sẽ gõ trên Terminal
    protected $signature = 'make:admin {name} {email} {password}';

    // Mô tả ngắn gọn chức năng của lệnh
    protected $description = 'Tạo tài khoản quản trị viên mới bảo mật từ Terminal';

    public function handle()
    {
        // Nhận dữ liệu từ Terminal và tạo user
        $user = User::create([
            'name' => $this->argument('name'),
            'email' => $this->argument('email'),
            'password' => Hash::make($this->argument('password')),
            // Cột google2fa_secret sẽ tự động bị bỏ trống để lát nữa ép cài 2FA
        ]);

        // In ra dòng thông báo chữ xanh thành công
        $this->info("Tuyet voi! Da tao thanh cong tai khoan: {$user->email}");
        $this->info("Hay dang nhap de thiet lap ma QR 2FA.");
    }
}