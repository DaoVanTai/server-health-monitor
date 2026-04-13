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