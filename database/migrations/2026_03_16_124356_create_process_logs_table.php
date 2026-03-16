<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('process_logs', function (Blueprint $table) {
            $table->id();
            $table->integer('pid');             // Mã định danh tiến trình
            $table->string('process_name');      // Tên tác vụ (ví dụ: php, node, mysql)
            $table->float('cpu_usage');         // % CPU mà tác vụ đó đang chiếm
            $table->float('ram_usage');         // % RAM mà tác vụ đó đang chiếm
            $table->timestamps();               // Thời gian ghi nhận
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('process_logs');
    }
};