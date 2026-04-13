<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\SystemLog;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class SystemLogController extends Controller
{
    public function index(Request $request)
    {
        $query = SystemLog::whereIn('level', ['danger', 'success', 'warning']);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('message', 'like', "%{$search}%")
                  ->orWhere('source', 'like', "%{$search}%")
                  ->orWhere('ip_address', 'like', "%{$search}%");
            });
        }

        if ($request->filled('level') && $request->level !== 'all') {
            $query->where('level', $request->level);
        }

        $fromDate = $request->input('from_date');
        $toDate = $request->input('to_date');

        if ($fromDate && $toDate) {
            $from = $fromDate . ' 00:00:00';
            $to = $toDate . ' 23:59:59';
            $query->whereBetween('created_at', [$from, $to]);
        }

        // 1. DỮ LIỆU FIREWALL (DATABASE)
        $dbLogs = $query->orderBy('created_at', 'desc')->get();

        // 2. DỮ LIỆU SSH TRACKER (TỪ OS)
        $sshData = $this->getSshLogData();

        // 3. BIỂU ĐỒ CHART.JS (7 Ngày)
        $chartLabels = []; $chartBlocked = []; $chartUnblocked = []; $chartAlert = [];
        
        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i)->format('Y-m-d');
            $chartLabels[] = Carbon::now()->subDays($i)->format('d/m');
            
            $chartBlocked[] = SystemLog::whereDate('created_at', $date)->where('level', 'danger')->count();
            $chartUnblocked[] = SystemLog::whereDate('created_at', $date)->where('level', 'success')->count();
            $chartAlert[] = SystemLog::whereDate('created_at', $date)->where('level', 'warning')->count();
        }

        return view('logs', compact(
            'dbLogs', 'sshData', 'chartLabels', 'chartBlocked', 'chartUnblocked', 'chartAlert', 'fromDate', 'toDate'
        ));
    }

    private function getSshLogData() {
        if (PHP_OS_FAMILY === 'Linux') {
            $logContent = shell_exec('sudo cat /var/log/auth.log | grep sshd | tail -n 500 2>/dev/null');
        } else {
            $logContent = null; 
        }

        if (!$logContent) {
            $logContent = "Mar 25 10:01:22 server sshd[101]: Failed password for root from 103.27.238.68 port 22 ssh2\nMar 25 10:10:00 server sshd[107]: Accepted publickey for root from 103.27.61.76 port 22 ssh2";
        }

        $failedAttempts = []; $successfulLogins = []; $targetedUsers = [];
        $lines = explode("\n", trim($logContent));

        foreach ($lines as $line) {
            if (empty(trim($line))) continue;
            preg_match('/^([a-zA-Z]{3}\s+\d+\s\d{2}:\d{2}:\d{2})/', $line, $timeMatches);
            $time = $timeMatches[1] ?? 'Unknown Time';

            if (preg_match('/Failed (?:password|publickey) for (?:invalid user )?([^\s]+) from ([0-9\.]+)/', $line, $matches)) {
                $user = $matches[1]; $ip = $matches[2];
                if (!isset($failedAttempts[$ip])) $failedAttempts[$ip] = ['count' => 0, 'users' => []];
                $failedAttempts[$ip]['count']++;
                if (!in_array($user, $failedAttempts[$ip]['users'])) $failedAttempts[$ip]['users'][] = $user;
                $targetedUsers[$user] = ($targetedUsers[$user] ?? 0) + 1;
            }

            if (preg_match('/Accepted (?:password|publickey) for ([^\s]+) from ([0-9\.]+)/', $line, $matches)) {
                $successfulLogins[] = ['time' => $time, 'user' => $matches[1], 'ip' => $matches[2]];
            }
        }

        arsort($targetedUsers);
        uasort($failedAttempts, function($a, $b) { return $b['count'] <=> $a['count']; });

        return [
            'topAttackers' => array_slice($failedAttempts, 0, 10, true),
            'recentLogins' => array_slice(array_reverse($successfulLogins), 0, 10)
        ];
    }
}