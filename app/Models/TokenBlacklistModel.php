<?php

namespace App\Models;

use CodeIgniter\Model;

class TokenBlacklistModel extends Model
{
    protected $table = 'token_blacklist';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = false;
    protected $returnType = 'array';
    protected $protectFields = true;
    protected $allowedFields = [
        'id',
        'jti',
        'expires_at',
        'revoked_at',
        'created_at',
        'reason',
    ];
    protected $useTimestamps = false;
}
