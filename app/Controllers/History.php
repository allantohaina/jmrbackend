<?php

namespace App\Controllers;

use CodeIgniter\RESTful\ResourceController;

class History extends ResourceController
{
    protected $format = 'json';

    public function audit()
    {
        return $this->respond($this->paginate('audit_logs'));
    }

    public function tokens()
    {
        return $this->respond($this->paginate('token_history'));
    }

    public function projects()
    {
        return $this->respond($this->paginate('order_project_history'));
    }

    private function paginate(string $table): array
    {
        $limit = (int) ($this->request->getGet('limit') ?? 50);
        $limit = max(1, min(200, $limit));
        $offset = (int) ($this->request->getGet('offset') ?? 0);
        $offset = max(0, $offset);

        $builder = db_connect()->table($table)->orderBy('created_at', 'DESC');

        $action = $this->request->getGet('action');
        if ($action) {
            $builder->where('action', $action);
        }

        $entityType = $this->request->getGet('entity_type');
        if ($entityType && $table === 'audit_logs') {
            $builder->where('entity_type', $entityType);
        }

        $rows = $builder->limit($limit, $offset)->get()->getResultArray();

        return [
            'data' => $rows,
            'limit' => $limit,
            'offset' => $offset,
        ];
    }
}
