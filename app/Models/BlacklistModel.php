<?php

namespace App\Models;

use CodeIgniter\Model;

class BlacklistModel extends Model
{
    protected $table = 'user_blacklist';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = false;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $protectFields = true;
    protected $allowedFields = ['email', 'ip_address', 'reason', 'created_at'];
    protected $useTimestamps = false;

    public function isBlacklisted(string $email, string $ip): bool
    {
        return $this->groupStart()
            ->where('email', $email)
            ->orWhere('ip_address', $ip)
            ->groupEnd()
            ->countAllResults() > 0;
    }
}
