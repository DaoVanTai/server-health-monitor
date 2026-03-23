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
        Schema::create('system_logs', function (Blueprint $table) {
            $table->id();
            $table->string('level'); // Mức độ: info (xanh), warning (vàng), danger (đỏ)
            $table->string('source'); // Nguồn phát hiện: Firewall, AI, System...
            $table->text('message'); // Nội dung sự kiện
            $table->string('ip_address')->nullable(); // IP liên quan (nếu có)
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('system_logs');
    }
};
