<?php

namespace App\Services;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class QueueHealthService
{
    private const HEARTBEAT_KEY = 'queue:worker:last-heartbeat-at';

    public function markHeartbeat(string $source = 'job'): void
    {
        Cache::put(self::HEARTBEAT_KEY, [
            'at' => now()->toIso8601String(),
            'source' => $source,
        ], now()->addHours(6));
    }

    /**
     * @return array<string, mixed>
     */
    public function snapshot(): array
    {
        $payload = Cache::get(self::HEARTBEAT_KEY);
        $lastHeartbeatAt = is_array($payload) ? (string) ($payload['at'] ?? '') : '';
        $lastSource = is_array($payload) ? (string) ($payload['source'] ?? '') : '';

        $pendingJobs = (int) DB::table('jobs')->count();
        $failedJobs = (int) DB::table('failed_jobs')->count();

        $heartbeatSecondsAgo = null;
        if ($lastHeartbeatAt !== '') {
            try {
                $heartbeatSecondsAgo = Carbon::parse($lastHeartbeatAt)->diffInSeconds(now());
            } catch (\Throwable) {
                $heartbeatSecondsAgo = null;
            }
        }

        $status = 'healthy';
        $message = 'Queue is healthy.';

        if ($pendingJobs > 0 && ($heartbeatSecondsAgo === null || $heartbeatSecondsAgo > 120)) {
            $status = 'degraded';
            $message = 'Jobs are pending and worker heartbeat is stale. Worker may be offline.';
        } elseif ($pendingJobs > 0) {
            $status = 'processing';
            $message = 'Worker heartbeat detected while jobs are pending.';
        } elseif ($heartbeatSecondsAgo !== null && $heartbeatSecondsAgo > 900) {
            $status = 'idle';
            $message = 'No pending jobs and no recent heartbeat detected.';
        }

        return [
            'status' => $status,
            'message' => $message,
            'pending_jobs' => $pendingJobs,
            'failed_jobs' => $failedJobs,
            'last_heartbeat_at' => $lastHeartbeatAt !== '' ? $lastHeartbeatAt : null,
            'last_heartbeat_source' => $lastSource !== '' ? $lastSource : null,
            'heartbeat_seconds_ago' => $heartbeatSecondsAgo,
        ];
    }
}
