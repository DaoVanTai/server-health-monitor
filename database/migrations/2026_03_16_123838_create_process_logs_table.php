<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Sử dụng 'return new class' để Laravel tự động nhận diện mà không cần khớp tên Class với tên file
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Kiểm tra nếu bảng chưa tồn tại thì mới tạo (tránh lỗi nếu lỡ chạy dở dang)
        if (!Schema::hasTable('process_logs')) {
            Schema::create('process_logs', function (Blueprint $table) {
                $table->id();
                $table->integer('pid');             // Mã định danh tiến trình
                $table->string('process_name');      // Tên ứng dụng (node, php, v.v.)
                $table->float('cpu_usage');         // % CPU của riêng nó
                $table->float('ram_usage');         // % RAM của riêng nó
                $table->timestamps();               // Lưu thời điểm ghi log (created_at, updated_at)
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('process_logs');
    }
};