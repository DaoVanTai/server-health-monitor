<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http; // PHẢI CÓ DÒNG NÀY
use Illuminate\Support\Facades\Cache; // PHẢI CÓ DÒNG NÀY

class NetworkController extends Controller
{
    /**
     * Lấy danh sách các kết nối mạng đang hoạt động (Active Connections)
     */
    public function getActiveConnections()
    {
        // 1. Chạy lệnh Linux để lấy dữ liệu thô
        $rawOutput = shell_exec('ss -tunp state established 2>/dev/null');

        if (empty($rawOutput)) {
            return response()->json([]);
        }

        // 2. Bắt đầu "băm" dữ liệu
        $lines = explode("\n", trim($rawOutput));
        $connections = [];

        foreach (array_slice($lines, 1) as $line) {
            $columns = preg_split('/\s+/', trim($line));
            if (count($columns) < 5) continue;

            // 3. Phân tích tên tiến trình
            $processName = 'Unknown';
            $rawProcess = end($columns);
            if (preg_match('/"([^"]+)"/', $rawProcess, $matches)) {
                $processName = $matches[1];
            }

            // 4. Phân tích IP và Cổng
            $local = $this->parseIpPort($columns[3]);
            $remote = $this->parseIpPort($columns[4]);

            // --- BƯỚC MỚI: TRA CỨU VỊ TRÍ ĐỊA LÝ ---
            $location = $this->getLocation($remote['ip']);

            // 5. Gom lại thành một bản ghi sạch sẽ
            $connections[] = [
                'protocol' => strtoupper($columns[0]),
                'local_ip' => $local['ip'],
                'local_port' => $local['port'],
                'remote_ip' => $remote['ip'],
                'remote_port' => $remote['port'],
                'process' => $processName,
                'location' => $location, // Đính kèm tọa độ và quốc gia vào đây
            ];
        }

        return response()->json($connections);
    }

    /**
     * Tách IP và Cổng
     */
    private function parseIpPort($string)
    {
        if (preg_match('/\[(.*)\]:(\d+)/', $string, $matches)) {
            return ['ip' => $matches[1], 'port' => $matches[2]];
        }
        $parts = explode(':', $string);
        $port = array_pop($parts);
        $ip = implode(':', $parts);
        
        return [
            'ip' => empty($ip) || $ip == '*' ? '0.0.0.0' : $ip, 
            'port' => $port
        ];
    }

    /**
     * Tra cứu vị trí IP (Geo-IP)
     */
    private function getLocation($ip) 
    {
        // Không tra cứu các IP nội bộ hoặc IP server
        if ($ip == '127.0.0.1' || $ip == '0.0.0.0' || strpos($ip, '192.168.') === 0 || $ip == '103.27.61.76') {
            return null;
        }

        // Cache trong 7 ngày để tránh tốn API limit (45 requests/min)
        return Cache::remember('geo_ip_' . $ip, now()->addDays(7), function () use ($ip) {
            try {
                // Gọi API ip-api.com để lấy tọa độ và thông tin vùng
                $response = Http::timeout(3)->get("http://ip-api.com/json/{$ip}?fields=status,country,city,lat,lon");
                if ($response->successful() && $response->json('status') == 'success') {
                    return $response->json();
                }
            } catch (\Exception $e) { 
                return null; 
            }
            return null;
        });
    }
}