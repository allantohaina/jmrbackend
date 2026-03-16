<?php

namespace App\Models;

use CodeIgniter\Model;

class ProductionChecklistModel extends Model
{
    protected $table = 'production_checklists';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = false;
    protected $returnType = 'array';
    protected $protectFields = true;
    protected $allowedFields = [
        'id',
        'project_id',
        'type',
        'status',
        'items',
        'created_at',
        'updated_at',
    ];
    protected $useTimestamps = false;
}
