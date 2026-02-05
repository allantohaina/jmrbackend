<?php

namespace App\Models;

use CodeIgniter\Model;

class TokenHistoryModel extends Model
{
    protected $table = 'token_history';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = false;
    protected $returnType = 'array';
    protected $protectFields = true;
    protected $allowedFields = [
        'id',
        'user_id',
        'action',
        'jti',
        'refresh_token_id',
        'meta',
        'ip_address',
        'user_agent',
        'created_at',
    ];
    protected $useTimestamps = false;
}
