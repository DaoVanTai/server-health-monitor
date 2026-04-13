<?php

// Tên file xuất ra
$outputFile = 'toan_bo_code_aegis.txt';

// Các thư mục quan trọng cần gộp (bạn có thể thêm bớt tùy ý)
$directories = [
    'app/Http/Controllers',
    'app/Http/Middleware',
    'app/Models',
    'routes',
    'resources/views', // Cẩn thận thư mục này có thể hơi dài
];

$output = fopen($outputFile, 'w');
fwrite($output, "=== TÀI LIỆU MÃ NGUỒN: SERVER HEALTH MONITORING & DETECTION SYSTEM ===\n\n");

foreach ($directories as $dir) {
    if (!is_dir($dir)) continue;

    $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));
    foreach ($files as $file) {
        // Chỉ lấy các file .php (bỏ qua các file rác hoặc ảnh)
        if ($file->isFile() && $file->getExtension() === 'php') {
            $filePath = $file->getPathname();
            $content = file_get_contents($filePath);
            
            // Ghi tên file làm tiêu đề phân cách
            fwrite($output, "========================================================\n");
            fwrite($output, "📁 FILE: " . str_replace('\\', '/', $filePath) . "\n");
            fwrite($output, "========================================================\n\n");
            fwrite($output, $content . "\n\n");
        }
    }
}

fclose($output);
echo "Tuyệt vời! Đã gộp toàn bộ code thành công vào file: " . $outputFile . "\n";

?>