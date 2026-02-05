<?php

namespace App\Models;

use CodeIgniter\Model;

class MigrationHistoryModel extends Model
{
    protected $table = 'migration_history';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = false;
    protected $returnType = 'array';
    protected $protectFields = true;
    protected $allowedFields = [
        'id',
        'migration',
        'direction',
        'batch',
        'meta',
        'executed_at',
    ];
    protected $useTimestamps = false;
}
