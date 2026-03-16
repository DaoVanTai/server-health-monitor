public function up(): void
{
    Schema::table('server_metrics', function (Blueprint $table) {
        // Thêm các cột để lưu lịch sử Network và chi tiết CPU
        $table->float('network_in')->default(0)->after('disk_percent');
        $table->float('network_out')->default(0)->after('network_in');
    });
}

public function down(): void
{
    Schema::table('server_metrics', function (Blueprint $table) {
        $table->dropColumn(['network_in', 'network_out']);
    });
}