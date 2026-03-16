<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProcessLog extends Model
{
    use HasFactory;

    // Khai báo các cột được phép lưu dữ liệu
    protected $fillable = [
        'pid',
        'process_name',
        'cpu_usage',
        'ram_usage'
    ];
}