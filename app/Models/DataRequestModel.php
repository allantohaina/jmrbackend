<?php

namespace App\Models;

use CodeIgniter\Model;

class DataRequestModel extends Model
{
    protected $table = 'data_requests';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = false;
    protected $returnType = 'array';
    protected $protectFields = true;
    protected $allowedFields = [
        'id',
        'user_id',
        'email',
        'request_type',
        'status',
        'details',
        'created_at',
        'updated_at',
        'completed_at',
        'ip_address',
        'user_agent',
    ];
    protected $useTimestamps = false;
}
