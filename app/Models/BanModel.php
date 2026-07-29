<?php

namespace App\Models;

use CodeIgniter\Model;

class BanModel extends Model
{
    protected $table = 'user_bans';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = false;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $protectFields = true;
    protected $allowedFields = ['user_id', 'banned_by', 'reason', 'expires_at', 'created_at'];
    protected $useTimestamps = false;

    public function getActiveBan(string $userId): ?array
    {
        return $this->where('user_id', $userId)
            ->where('expires_at IS NULL OR expires_at > CURRENT_TIMESTAMP')
            ->orderBy('created_at', 'DESC')
            ->first();
    }
}
