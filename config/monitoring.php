<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Ngưỡng cảnh báo (Warning Thresholds)
    |--------------------------------------------------------------------------
    |
    | Cấu hình ngưỡng phần trăm để kích hoạt cảnh báo cho từng chỉ số.
    |
    */

    'thresholds' => [
        'ram' => (int) env('MONITOR_RAM_THRESHOLD', 80),
        'disk' => (int) env('MONITOR_DISK_THRESHOLD', 90),
        'cpu' => (int) env('MONITOR_CPU_THRESHOLD', 80),
    ],

];
