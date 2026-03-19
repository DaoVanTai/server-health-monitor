<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class NetworkController extends Controller
{
    /**
     * Lấy danh sách các kết nối mạng đang hoạt động (Active Connections)
     */
    public function getActiveConnections()
    {
        // 1. Chạy lệnh Linux để lấy dữ liệu thô
        // Chúng ta dùng '2>/dev/null' để loại bỏ các thông báo lỗi quyền truy cập
        // Sửa từ 'ss -tunp established' thành:
$rawOutput = shell_exec('ss -tunp state established 2>/dev/null');

        if (empty($rawOutput)) {
            return response()->json([]); // Trả về mảng rỗng nếu không có dữ liệu
        }

        // 2. Bắt đầu "băm" dữ liệu
        // Tách dữ liệu thô thành từng dòng
        $lines = explode("\n", trim($rawOutput));
        $connections = [];

        // Bỏ qua dòng tiêu đề đầu tiên
        foreach (array_slice($lines, 1) as $line) {
            // Tách các cột dựa trên khoảng trắng
            $columns = preg_split('/\s+/', trim($line));

            if (count($columns) < 5) continue; // Bỏ qua nếu dòng bị thiếu dữ liệu

            // 3. Phân tích tên tiến trình (Cột cuối cùng, ví dụ: users:(("nginx",pid=123,fd=4)))
            $processName = 'Unknown';
            $rawProcess = end($columns);
            if (preg_match('/"([^"]+)"/', $rawProcess, $matches)) {
                $processName = $matches[1]; // Lấy chữ "nginx"
            }

            // 4. Phân tích IP và Cổng Nguồn/Đích
            // Định dạng thô thường là [IP]:Cổng hoặc IP:Cổng
            $local = $this->parseIpPort($columns[3]);
            $remote = $this->parseIpPort($columns[4]);

            // 5. Gom lại thành một bản ghi sạch sẽ
            $connections[] = [
                'protocol' => strtoupper($columns[0]), // TCP hoặc UDP
                'local_ip' => $local['ip'],
                'local_port' => $local['port'],
                'remote_ip' => $remote['ip'], // IP của khách hàng
                'remote_port' => $remote['port'],
                'process' => $processName, // Ứng dụng xử lý
            ];
        }

        // 6. Trả về dữ liệu JSON cho Frontend vẽ bảng
        return response()->json($connections);
    }

    /**
     * Hàm phụ để tách IP và Cổng ra khỏi chuỗi dạng IP:Port hoặc [IPv6]:Port
     */
    private function parseIpPort($string)
    {
        // Xử lý IPv6 có ngoặc vuông [::1]:80
        if (preg_match('/\[(.*)\]:(\d+)/', $string, $matches)) {
            return ['ip' => $matches[1], 'port' => $matches[2]];
        }
        // Xử lý IPv4 thông thường 1.2.3.4:80
        $parts = explode(':', $string);
        $port = array_pop($parts);
        $ip = implode(':', $parts);
        
        return [
            'ip' => empty($ip) || $ip == '*' ? '0.0.0.0' : $ip, 
            'port' => $port
        ];
    }
}