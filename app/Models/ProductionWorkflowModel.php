<?php

namespace App\Models;

use CodeIgniter\Model;

class ProductionWorkflowModel extends Model
{
    protected $table = 'production_workflows';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = false;
    protected $returnType = 'array';
    protected $protectFields = true;
    protected $allowedFields = [
        'id',
        'project_id',
        'name',
        'workflow_type',
        'client_name',
        'approval_date',
        'delivery_date',
        'launch_date',
        'status',
        'current_step_id',
        'last_validated_step_id',
        'styles',
        'measurements',
        'production_notes',
        'steps',
        'history',
        'rollback_context',
        'created_at',
        'updated_at',
    ];
    protected $useTimestamps = false;
}
