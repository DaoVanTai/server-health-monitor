public function up(): void
{
    Schema::create('process_logs', function (Blueprint $table) {
        $table->id();
        $table->integer('pid');             // Mã định danh tiến trình
        $table->string('process_name');      // Tên ứng dụng (node, php, v.v.)
        $table->float('cpu_usage');         // % CPU của riêng nó
        $table->float('ram_usage');         // % RAM của riêng nó
        $table->timestamps();               // Lưu thời điểm ghi log
    });
}