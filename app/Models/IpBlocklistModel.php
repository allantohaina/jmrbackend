<?php

namespace App\Models;

use CodeIgniter\Model;

class IpBlocklistModel extends Model
{
    protected $table = 'ip_blocklist';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = false;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $protectFields = true;
    protected $allowedFields = ['ip_address', 'reason', 'failed_attempts', 'blocked_at', 'expires_at', 'is_active', 'created_at'];
    protected $useTimestamps = false;

    public function isBlocked(string $ip): bool
    {
        return $this->where('ip_address', $ip)
            ->where('is_active', true)
            ->where('expires_at IS NULL OR expires_at > CURRENT_TIMESTAMP')
            ->countAllResults() > 0;
    }

    public function getActiveBlock(string $ip): ?array
    {
        return $this->where('ip_address', $ip)
            ->where('is_active', true)
            ->where('expires_at IS NULL OR expires_at > CURRENT_TIMESTAMP')
            ->first();
    }

    public function incrementFailedAttempts(string $ip, int $maxAttempts = 10): void
    {
        $existing = $this->where('ip_address', $ip)->first();

        if ($existing) {
            $attempts = ($existing['failed_attempts'] ?? 0) + 1;
            $data = ['failed_attempts' => $attempts];

            if ($attempts >= $maxAttempts) {
                $data['is_active'] = true;
                $data['reason'] = 'brute_force';
                $data['blocked_at'] = date('Y-m-d H:i:s');
                $data['expires_at'] = date('Y-m-d H:i:s', strtotime('+15 minutes'));
            }

            $this->update($existing['id'], $data);
        } else {
            $this->insert([
                'ip_address' => $ip,
                'failed_attempts' => 1,
                'reason' => null,
                'blocked_at' => date('Y-m-d H:i:s'),
                'expires_at' => null,
                'is_active' => false,
                'created_at' => date('Y-m-d H:i:s'),
            ]);
        }
    }

    public function clearFailedAttempts(string $ip): void
    {
        $existing = $this->where('ip_address', $ip)->first();
        if ($existing) {
            $this->update($existing['id'], [
                'failed_attempts' => 0,
                'is_active' => false,
                'reason' => null,
                'expires_at' => null,
            ]);
        }
    }
}
