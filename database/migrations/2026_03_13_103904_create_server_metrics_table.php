<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('server_metrics', function (Blueprint $table) {
            $table->id();
            // Thêm 3 dòng này để lưu dữ liệu
            $table->float('cpu_percent');
            $table->float('ram_percent');
            $table->float('disk_percent');
            
            $table->timestamps(); // Dòng này tự động tạo cột created_at (thời gian lưu)
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('server_metrics');
    }
};
