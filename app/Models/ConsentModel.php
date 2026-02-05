<?php

namespace App\Models;

use CodeIgniter\Model;

class ConsentModel extends Model
{
    protected $table = 'user_consents';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = false;
    protected $returnType = 'array';
    protected $protectFields = true;
    protected $allowedFields = [
        'id',
        'user_id',
        'subject',
        'version',
        'granted',
        'granted_at',
        'revoked_at',
        'ip_address',
        'user_agent',
    ];
    protected $useTimestamps = false;
}
