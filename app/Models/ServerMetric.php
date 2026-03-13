<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ServerMetric extends Model
{
    use HasFactory;

    // Cấp quyền cho phép ghi hàng loạt vào Database
    protected $fillable = ['cpu_percent', 'ram_percent', 'disk_percent'];
}