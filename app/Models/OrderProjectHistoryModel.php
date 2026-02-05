<?php

namespace App\Models;

use CodeIgniter\Model;

class OrderProjectHistoryModel extends Model
{
    protected $table = 'order_project_history';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = false;
    protected $returnType = 'array';
    protected $protectFields = true;
    protected $allowedFields = [
        'id',
        'project_id',
        'status',
        'action',
        'details',
        'actor_user_id',
        'created_at',
    ];
    protected $useTimestamps = false;
}
