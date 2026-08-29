<?php

namespace Jidaikobo\Kontiki\Services;

use Illuminate\Database\Connection;
use Jidaikobo\Kontiki\Core\Database;

class RateLimitService
{
    private Connection $db;
    private int $blockDuration = 900; // 15 minutes
    private int $limitDuration = 180; // 3 minutes
    private int $maxAttempts = 5;     // Max 5 attempts

    public function __construct(Database $db)
    {
        $this->db = $db->getConnection();
    }

    public function recordFailedLogin(string $ip): void
    {
        $now = time();
        $record = $this->getRateLimitRecord($ip);

        if ($record) {
            $this->db->table('rate_limit')
                ->where('ip_address', $ip)
                ->update([
                    'attempt_count' => $record->attempt_count + 1,
                    'last_attempt' => $now,
                ]);
        } else {
            $this->db->table('rate_limit')->insert([
                'ip_address' => $ip,
                'attempt_count' => 1,
                'first_attempt' => $now,
                'last_attempt' => $now,
            ]);
        }
    }

    public function isIpBlocked(string $ip): bool
    {
        $record = $this->getRateLimitRecord($ip);
        if (!$record) {
            return false;
        }

        if ($this->isCurrentlyBlocked($record)) {
            return true;
        }
        if ($this->shouldBlockIp($record)) {
            $this->blockIp($ip);
            return true;
        }
        return false;
    }

    public function resetRateLimit(string $ip): void
    {
        $this->db->table('rate_limit')
            ->where('ip_address', $ip)
            ->delete();
    }

    public function cleanOldRateLimitData(): void
    {
        $threshold = time() - (7 * 24 * 60 * 60); // 7 days
        $this->db->table('rate_limit')
            ->where('last_attempt', '<', $threshold)
            ->delete();
    }

    private function getRateLimitRecord(string $ip)
    {
        return $this->db->table('rate_limit')
            ->where('ip_address', $ip)
            ->first();
    }

    private function isCurrentlyBlocked($record): bool
    {
        return !is_null($record->blocked_until) && $record->blocked_until > time();
    }

    private function shouldBlockIp($record): bool
    {
        return $record->attempt_count >= $this->maxAttempts &&
               (time() - $record->first_attempt) <= $this->limitDuration;
    }

    private function blockIp(string $ip): void
    {
        $this->db->table('rate_limit')
            ->where('ip_address', $ip)
            ->update(['blocked_until' => time() + $this->blockDuration]);
    }
}
