<?php

namespace App\Models;

use CodeIgniter\Model;

class RefreshTokenModel extends Model
{
    protected $table = 'refresh_tokens';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = false;
    protected $returnType = 'array';
    protected $protectFields = true;
    protected $allowedFields = [
        'id',
        'user_id',
        'token_hash',
        'expires_at',
        'revoked_at',
        'replaced_by',
        'created_at',
        'ip_address',
        'user_agent',
    ];
    protected $useTimestamps = false;
}
