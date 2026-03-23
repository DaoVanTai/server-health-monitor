<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http; 
use Illuminate\Support\Facades\Cache; 
use App\Models\Blacklist; // <-- ĐỌC SỔ ĐEN

class NetworkController extends Controller
{
    public function getActiveConnections()
    {
        // 1. Lấy toàn bộ IP đã bị khóa
        $blockedIps = Blacklist::pluck('ip_address')->toArray();

        // 2. Chạy lệnh Linux để lấy dữ liệu
        $rawOutput = shell_exec('ss -tunp state established 2>/dev/null');

        if (empty($rawOutput)) {
            return response()->json([]);
        }

        $lines = explode("\n", trim($rawOutput));
        $connections = [];

        foreach (array_slice($lines, 1) as $line) {
            $columns = preg_split('/\s+/', trim($line));
            if (count($columns) < 5) continue;

            $processName = 'Unknown';
            $rawProcess = end($columns);
            if (preg_match('/"([^"]+)"/', $rawProcess, $matches)) {
                $processName = $matches[1];
            }

            $local = $this->parseIpPort($columns[3]);
            $remote = $this->parseIpPort($columns[4]);
            $location = $this->getLocation($remote['ip']);
            
            // ==========================================
            // ĐÁNH DẤU IP ĐÃ BỊ CHẶN
            // ==========================================
            $isBlocked = in_array($remote['ip'], $blockedIps);

            $connections[] = [
                'protocol' => strtoupper($columns[0]),
                'local_ip' => $local['ip'],
                'local_port' => $local['port'],
                'remote_ip' => $remote['ip'],
                'remote_port' => $remote['port'],
                'process' => $processName,
                'location' => $location,
                'is_blocked' => $isBlocked, // Truyền trạng thái này ra ngoài Giao diện
            ];
        }

        // ==========================================
        // SẮP XẾP: ĐẨY KẺ BỊ CHẶN XUỐNG ĐÁY BẢNG
        // ==========================================
        usort($connections, function($a, $b) {
            // is_blocked = false (0) sẽ nằm trên, true (1) sẽ bị đẩy xuống dưới
            return $a['is_blocked'] <=> $b['is_blocked'];
        });

        return response()->json($connections);
    }

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

    private function getLocation($ip) 
    {
        if ($ip == '127.0.0.1' || $ip == '0.0.0.0' || strpos($ip, '192.168.') === 0 || $ip == '103.27.61.76') {
            return null;
        }

        return Cache::remember('geo_ip_' . $ip, now()->addDays(7), function () use ($ip) {
            try {
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