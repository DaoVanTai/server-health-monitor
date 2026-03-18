<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Sử dụng anonymous class để tránh lỗi "Class not found"
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('server_metrics', function (Blueprint $table) {
            // Kiểm tra nếu cột chưa tồn tại thì mới thêm (tránh lỗi nếu chạy lại)
            if (!Schema::hasColumn('server_metrics', 'network_in')) {
                $table->float('network_in')->default(0)->after('disk_percent');
            }
            if (!Schema::hasColumn('server_metrics', 'network_out')) {
                $table->float('network_out')->default(0)->after('network_in');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('server_metrics', function (Blueprint $table) {
            $table->dropColumn(['network_in', 'network_out']);
        });
    }
};