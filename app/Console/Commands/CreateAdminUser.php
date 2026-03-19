<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator; // Thêm thư viện kiểm tra dữ liệu

class CreateAdminUser extends Command
{
    protected $signature = 'make:admin {name} {email} {password}';
    protected $description = 'Tạo tài khoản quản trị viên mới bảo mật từ Terminal';

    public function handle()
    {
        // 1. Gom dữ liệu bạn gõ trên Terminal vào một mảng
        $data = [
            'name' => $this->argument('name'),
            'email' => $this->argument('email'),
            'password' => $this->argument('password'),
        ];

        // 2. Dựng rào chắn kiểm tra (Chính là quy tắc 8 ký tự của bạn)
        $validator = Validator::make($data, [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users', // Ép email phải chuẩn và không trùng
            'password' => 'required|string|min:8', // Ép buộc TỐI THIỂU 8 KÝ TỰ
        ]);

        // 3. Nếu gõ sai quy tắc -> Báo lỗi đỏ và dừng lại ngay
        if ($validator->fails()) {
            $this->error('Tao tai khoan that bai! Vui long sua cac loi sau:');
            foreach ($validator->errors()->all() as $error) {
                $this->error(' - ' . $error);
            }
            return 1; // Báo hiệu lệnh chạy thất bại
        }

        // 4. Nếu qua được rào chắn -> Tạo tài khoản thật
        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
        ]);

        $this->info("Tuyet voi! Da tao thanh cong tai khoan: {$user->email}");
        $this->info("Mat khau da duoc ma hoa an toan.");
        return 0; // Báo hiệu lệnh chạy thành công
    }
}