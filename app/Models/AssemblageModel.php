<?php

namespace App\Models;

use CodeIgniter\Model;

class AssemblageModel extends Model
{
    protected $table = 'assemblages';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = false;
    protected $returnType = 'array';
    protected $protectFields = true;
    protected $allowedFields = [
        'id',
        'project_id',
        'name',
        'status',
        'details',
        'created_at',
        'updated_at',
    ];
    protected $useTimestamps = false;
}
