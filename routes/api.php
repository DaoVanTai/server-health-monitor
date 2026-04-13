<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ApiController; 

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// ROUTE CUNG CẤP DỮ LIỆU CHO BIỂU ĐỒ LỊCH SỬ (6H) & ANOMALIES
Route::get('/metrics/history', [ApiController::class, 'getHistory']);
// Route cho dữ liệu Lịch sử (bạn đã có)
Route::get('/metrics/history', [ApiController::class, 'getHistory']);

// THÊM DÒNG NÀY: Route cho dữ liệu Real-time (Cập nhật mỗi 5s)
Route::get('/server-status', [ApiController::class, 'getStatus']);